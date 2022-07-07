import requests
import pandas as pd
import csv
import psycopg2
import time
import os
from datetime import datetime, timedelta
from sqlalchemy import create_engine
from postmarker.core import PostmarkClient
from tqdm import tqdm

# Schedule using cron like this.
# 30 6 * * *  /usr/bin/python3 /var/www/html/secure/nightly-beehiiv-status-check.py

# Wrap in try/except to alert on errors.
try:

    # Start the session.
    session = requests.Session()

    # Create the payload.
    payload = {
                'email': '-',
                'password': '-'
              }
    headers = {
                'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language': 'en-US,en;q=0.9',
                'Accept-Encoding': 'gzip, deflate, br'
              }

    # Post the payload to the site to log in.
    s = session.post("-", data=payload, headers=headers)

    # Grab subscribers from export page.
    beehiiv_data = session.get('-')

    # Write Beehiiv data to CSV.
    with open('/var/www/html/secure/temp/beehiiv_data.csv', 'w') as f:
        writer = csv.writer(f)
        for line in beehiiv_data.iter_lines():
            writer.writerow(line.decode('utf-8').split(','))

    # Read in Beehiiv data.
    beehiiv_df = pd.read_csv('/var/www/html/secure/temp/beehiiv_data.csv')
    beehiiv_df['status'] = beehiiv_df['status'].replace({"active":"Active", "inactive":"Inactive"})
    beehiiv_df['created_at'] = pd.to_datetime(beehiiv_df['created_at'])
    beehiiv_df['created_at'] = beehiiv_df['created_at'].dt.tz_localize(None)

    # Pull data from database.
    engine = create_engine('-')
    database_df = pd.read_sql("SELECT email, status FROM main_newsletter", engine)
    database_set = set(database_df['email'].tolist())

    # Sync status between database and Beehiiv.
    # Open database connection.
    conn = psycopg2.connect(dbname='-',
                            user='-',
                            password='-',
                            host='-',
                            port='-',
                            sslmode='-')
    cur = conn.cursor()

    # Loop over all users in database.
    for db_item in tqdm(database_set):

        # Pull individual rows from database and Beehiiv.
        db_row = database_df[database_df['email'] == db_item]
        beehiiv_row = beehiiv_df[beehiiv_df['email'] == db_item]

        # Make sure row exists once in both environments.
        if (len(db_row) == len(beehiiv_row)) and (len(db_row) == 1):

            # Grab actual status from both.
            db_status = db_row['status'].iloc[0]
            beehiiv_status = beehiiv_row['status'].iloc[0]

            # If they aren't equal both places, process further.
            if db_status != beehiiv_status:

                # Update status in database to Beehiiv status.
                if beehiiv_status == 'Active':

                    # Reactive user in database.
                    resubscribed_timestamp = (datetime.utcnow()).strftime("%Y-%m-%d %H:%M:%S")
                    cur.execute("""UPDATE main_newsletter SET status = 'Active',
                                   number_times_resubscribed = number_times_resubscribed + 1,
                                   resubscribed_timestamp_utc = %s
                                   WHERE email = %s""",
                               (resubscribed_timestamp, db_item))
                    conn.commit()

                elif beehiiv_status == 'Inactive':

                    # Unsubscribe user in database.
                    unsubscribed_timestamp = (datetime.utcnow()).strftime("%Y-%m-%d %H:%M:%S")
                    cur.execute("""UPDATE main_newsletter SET status = 'Inactive',
                                   number_times_unsubscribed = number_times_unsubscribed + 1,
                                   unsubscribed_timestamp_utc = %s
                                   WHERE email = %s""",
                               (unsubscribed_timestamp, db_item))
                    conn.commit()

    # Close database connections.
    cur.close()
    conn.close()

    # Remove data file.
    os.remove('/var/www/html/secure/temp/beehiiv_data.csv')

# Catch exceptions.
except Exception as e:

    # Send server alert.
    base_string = '<html><body>Issue with nightly status comparison of Beehiiv against database. See info on exception below.<br><br>'
    error_string = str(e)
    email_string = base_string + error_string + '</body></html>'
    postmark = PostmarkClient(server_token='-')
    postmark.emails.send(
                         From='-',
                         To='-',
                         Subject='Server Alert',
                         HtmlBody=email_string,
                         TextBody="Please view the HTML content of this email for the message alert.",
                         Tag='server-alerts',
                         TrackOpens=True,
                         TrackLinks="None",
                         MessageStream='server-alerts'
                        )
