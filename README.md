![Sideline Sprint logo](/img/text-logo-large.png)

![Sideline Sprint newsletter](/img/newsletter.png)

# Sideline Sprint Tools Website

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

Sensitive information including API keys, logins, etc. have been masked.

Packages used:

-   [Hashids](https://github.com/vinkla/hashids)
-   [Createsend API SDK](https://github.com/campaignmonitor/createsend-php)
-   [Spaces API](https://github.com/SociallyDev/Spaces-API)
-   [Auth0](https://github.com/auth0/auth0-PHP)
-   [BunnyCDN API](https://github.com/BunnyWay/BunnyCDN.PHP.Storage)

## Architecture

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