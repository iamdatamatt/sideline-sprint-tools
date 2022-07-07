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
# 0 * * * * /usr/bin/python3 /var/www/html/secure/hourly-beehiiv-new-user-check.py

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

    # If in Beehiiv but not in database, add to database.
    # Filter to signups in last 1.5 hours.
    lookback_time = (datetime.utcnow() - timedelta(minutes=90)).strftime("%Y-%m-%d %H:%M:%S")
    recent_signups = beehiiv_df[beehiiv_df['created_at'] >= lookback_time]
    recent_set = set(recent_signups['email'].tolist())

    # Check if they're already in database.
    missing_subscribers = recent_set.difference(database_set)

    # If they aren't, add them. This will likely be Revue and referral subscribers.
    if len(missing_subscribers) > 0:

        # Insert into database via our API
        for missing_email in tqdm(missing_subscribers):

            # Send POST with data.
            api_payload = {'email': missing_email, 'x-sprint-key': '-'}
            api_resp = session.post('https://www.sidelinesprint.com/api/add-subscriber', data=api_payload)

            # Rate limit adding the subscribers.
            time.sleep(0.1)

    # Remove data file.
    os.remove('/var/www/html/secure/temp/beehiiv_data.csv')

# Catch exceptions.
except Exception as e:

    # Send server alert.
    base_string = '<html><body>Issue with hourly check to push new users from Beehiiv to database. See info on exception below.<br><br>'
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
