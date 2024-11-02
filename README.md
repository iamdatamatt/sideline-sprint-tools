![Sideline Sprint logo](/img/text-logo-large.png)

![Sideline Sprint newsletter](/img/newsletter.png)

# Sideline Sprint Tools Website

## What was Sideline Sprint?

Sideline Sprint was a daily sports email newsletter. The goal was to deliver the best of the sports world directly to your inbox every weekday morning. Sideline Sprint was in operation from the beginning of 2021 up until March of 2022.

## What was my role?

I served as a co-founder & the tech lead for the project (but also took on many other tasks as is often the case in startups).

## What did I do?

During my time working on Sideline Sprint, I worked on a variety of unique tasks including but not limited to:
- Built a website from scratch and leveraged cloud infrastructure
- Ensured email alignment on DMARC/DKIM/SPF/BIMI to achieve best-in-class deliverability with an average open rate of ~50%
- Posted all newsletters as articles to our Ghost blog to promote SEO
- Monitored SEO and improved ranking/clicks drastically over our 1.5 years in operation
- Built a referral program from scratch so that readers could bring in others at a low cost per acquisition (CPA)
- Designed the logo and graphics for the website, as well as merchandise for the referral program
- Created a custom responsive email template and setup a custom newsletter writing platform based on TinyMCE
- Setup & administered all of the tools listed in the below sections

## What does this repo contain?

This repository hosts all of the code for the Sideline Sprint tools website.

Our main infrastructure was made up of two websites:
- The main website for signups, hosting articles, etc.
- A second private website with tools built just for newsletter staff

The code in this repo relates to the staff website. I built tools from scratch to
manage a variety of functions and increase efficiency of managing the newsletter.
These tools included:
- Overview stats dashboards on campaigns, subscriber lists, and more
- Custom newsletter editor, based upon TinyMCE
- Image upload tool
- Subscriber management tools (search, update values, etc.)
- Uploading articles to our self-hosted Ghost blog

I found that in-house built tools often fit our use cases better than out-of-the box solutions.

Sensitive information including API keys, logins, etc. have been masked.

## Packages used

- [Hashids](https://github.com/vinkla/hashids) (generating unique user IDs)
- [Createsend API SDK](https://github.com/campaignmonitor/createsend-php) (interacting with Campaign Monitor via API)
- [Spaces API](https://github.com/SociallyDev/Spaces-API) (interacting with DigitalOcean Spaces via API)
- [Auth0](https://github.com/auth0/auth0-PHP) (user authentication for restricted staff tools)
- [BunnyCDN API](https://github.com/BunnyWay/BunnyCDN.PHP.Storage) (interacting with Bunny CDN via API)
- [Postmark PHP SDK](https://github.com/ActiveCampaign/postmark-php) (interacting with Postmark via API for transactional and alerting emails)

## Other tools I used

- [DigitalOcean](https://www.digitalocean.com/) (cloud hosting platform for our website and database)
- [Ghost CMS](https://ghost.org/) (hosting our newsletters published to a blog for SEO purposes)
- [Mailjet Markup Language (MJML)](https://mjml.io/) (creating our email templates)
- [Google Search Console](https://search.google.com/search-console/about) (SEO & search monitoring)
- [Google Analytics](https://analytics.google.com/) (monitoring website traffic & acquisition)
- [Google Postmaster Tools](https://www.gmail.com/postmaster/) (monitoring email authentication & deliverability)
- [DMARC Digests](https://dmarcdigests.com/) (monitoring email authentication & deliverability)
- [MailerLite](https://www.mailerlite.com/) (first email provider, used for a few months)
- [Campaign Monitor](https://www.campaignmonitor.com/) (second email provider, used for approximately 1 year)
- [Beehiiv](https://www.beehiiv.com/) (third & final email provider, used for a few months)
- [Google Workspace](https://workspace.google.com/) (collaboration amongst staff)
- [Google Domains](https://domains.google/) (website registration)
- [Bunny CDN](https://bunny.net/) (serving static assets to website)
- [Bitwarden](https://bitwarden.com/) (sharing of passwords amongst staff)
- [Auth0](https://auth0.com/) (access management for staff tools website)
- [Postmark](https://postmarkapp.com/) (transactional emails)
- [Ahrefs](https://ahrefs.com/) (SEO monitoring)
- [Google Ads](https://ads.google.com/home/) (advertising campaigns)
- [Reddit Ads](https://ads.reddit.com/) (advertising campaigns)
- [Affinity Photo & Designer](https://affinity.serif.com/en-us/) (logo design, website graphics, social graphics)

## Related works

If you're interested in seeing the other work I did for Sideline Sprint, please take a look at the following repos:
- [Sideline Sprint Main Website](https://github.com/iamdatamatt/sideline-sprint-website)
- [Sideline Sprint Miscellaneous Tools](https://github.com/iamdatamatt/sideline-sprint-misc)

## Architecture
![Sideline Sprint architecture diagram](/img/architecture-diagram.png)

## Screenshots

### Tools Site
![Sideline Sprint tools site](/img/tools-site.png)

### Subscriber Overview Dashboard
![Sideline Sprint subscriber overview dashboard](/img/overview-dashboard.png)

### Website Article Uploader
![Sideline Sprint website article uploader](/img/website-article-uploader.png)

### Image Uploader
![Sideline Sprint image uploader](/img/image-uploader.png)

### Single Subscriber Search
![Sideline Sprint single subscriber search](/img/single-subscriber-search.png)

### Bulk Subscriber Search
![Sideline Sprint bulk subscriber search](/img/bulk-subscriber-search.png)
