from simplegmail import Gmail
from simplegmail.query import construct_query
from postmarker.core import PostmarkClient
from BunnyCDN.Storage import Storage
import urllib
import os
from datetime import datetime
from bs4 import BeautifulSoup
import requests
import time

# Schedule using cron like this.
# 15 * * * *  /usr/bin/python3 /var/www/html/secure/email-to-bunny-cdn.py

# Wrap in try/except to alert on errors.
try:

    # Get HTML of most recent email.
    gmail = Gmail(client_secret_file='/var/www/html/secure/client_secret.json', creds_file='/var/www/html/secure/gmail_token.json')
    query_params = {
                    "newer_than": (12, "hour"),
                    "sender": "-",
                    "recipient": "-"
                    }
    messages = gmail.get_messages(query=construct_query(query_params))

    # Check if any messages exist.
    if len(messages) > 0:

        # Grab HTML.
        message_html = messages[0].html

        # Get other parameters of email.
        message_subject = messages[0].subject
        message_date = messages[0].date

        # Get inbox preview.
        soup = BeautifulSoup(message_html, 'html.parser')
        all_divs = soup.find_all('div')
        message_preview = all_divs[0].text.strip()

        # We have to pad with encoding and HTML tags as Gmail doesn't provide these.
        message_html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' + message_subject  + '</title><meta name="description" content="' + message_preview + '"></head>' + message_html + '</html>'

        # Create filename and location.
        file_name = message_subject + ' - ' + str(int(time.time() * 1000000)) + '.html'
        server_path = '/var/www/html/secure/temp/'
        local_full_path = os.path.join(server_path, file_name)

        # Remove personalization links.
        soup = BeautifulSoup(message_html, 'html.parser')
        for a in soup.find_all('a', href=True):
            if 'Unsubscribe' in a.text.strip():
                unsubscribe_url = a['href']
                message_html = message_html.replace(unsubscribe_url, '#')
            if 'Read Online' in a.text.strip():
                read_online_url = a['href']
                message_html = message_html.replace(read_online_url, '#')
            if 'Click to Share' in a.text.strip():
                share_url = a['href']
                message_html = message_html.replace(share_url, '#')
            if 'https://newsletter.sidelinesprint.com/subscribe?ref' in a.text.strip():
                copy_url = a['href']
                message_html = message_html.replace(copy_url, 'https://www.sidelinesprint.com/')

        # Replace tracking links with normal links.
        soup = BeautifulSoup(message_html, 'html.parser')
        headers = {
                    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'Accept-Language': 'en-US,en;q=0.9',
                    'Accept-Encoding': 'gzip, deflate, br'
                  }
        for a in soup.find_all('a', href=True):
            original_url = a['href']
            if original_url != "#":
                try:
                    r = requests.get(original_url, headers=headers)
                    replacement_url = r.url
                    message_html = message_html.replace(original_url, replacement_url)
                except:
                    pass

        # Remove final personalization link from share image.
        soup = BeautifulSoup(message_html, 'html.parser')
        for a in soup.find_all('a', href=True):
            if 'https://newsletter.sidelinesprint.com/subscribe' in a['href'].strip():
                image_url = a['href']
                message_html = message_html.replace(image_url, '#')

        # Replace background color.
        message_html = message_html.replace('background-color:#f0f0f0;', 'background-color:#ffffff;')

        # Remove form links.
        message_html = message_html.replace('-', '#')
        message_html = message_html.replace('-', '#')
        message_html = message_html.replace('-', '#')

        # Remove tracking pixel.
        soup = BeautifulSoup(message_html, 'html.parser')
        for img in soup.find_all('img'):
            tracking_url = img['src'].strip()
            if ('http://url4637.sidelinesprint.com' in tracking_url) and ('.gif' in tracking_url):
                message_html = message_html.replace(tracking_url, '#')
            elif ('https://url4637.sidelinesprint.com' in tracking_url) and ('.gif' in tracking_url):
                message_html = message_html.replace(tracking_url, '#')

        # Replace http with https.
        message_html = message_html.replace('http://', 'https://')

        # Save HTML to local file.
        with open(local_full_path, "w") as file:
            file.write(message_html)

        # Authenticate with CDN.
        bunny_storage_key = "-";
        bunny_storage_name = "-";
        bunny_storage_region = "-"

        # Set path to save to on CDN.
        month = datetime.now().strftime("%m")
        year = datetime.now().strftime("%Y")
        cdn_path = os.path.join('newsletters', 'html', str(year), str(month), file_name)

        # Push to Bunny storage.
        obj_storage = Storage(bunny_storage_key, bunny_storage_name, bunny_storage_region)
        put_storage = obj_storage.PutFile(file_name, storage_path=cdn_path, local_upload_file_path=server_path)
        if put_storage['status'] != 'success':
            raise Exception('Error pushing to Bunny CDN. ' + put_storage['msg'])

        # Delete local file.
        os.remove(local_full_path)

# Catch exceptions.
except Exception as e:

    # Send server alert.
    base_string = '<html><body>Issue with moving email HTML to Bunny CDN. See info on exception below.<br><br>'
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
