
# Aggregate RSS to Instapaper
A PHP script to read from a group of RSS feeds and post new items to Instapaper automatically using their simple API. This script was built to support adding updated news items to my Kobo reader through the Instapaper feature. 

## Tech stack
| **Tech** | **Version** |
|:--|:--|
| **PHP** | 7.x |
| **Composer** | 2.x |

## Composer libraries
The following composer libraries are required. 
* `vlucas/phpdotenv`

## Getting Started
Download this repo and create a .env file that includes your Instapaper username and password.  
**.env**
```
INSTA_USER="username"
INSTA_PW="password"
```
Also, ensure that the `feeds.json` file is writable by the user running the script. 

Once this file has been created, and you have configured the feeds included in `feeds.json`, you are ready to run the PHP script. To do so, run the following in a terminal. This will read through your list of RSS feeds and grab all feed items added since the last time the script ran. The time the script was last run is saved in `feeds.json` in the `last_updated` attribute. When the script is complete, it will update the `last_updated` value in your `feeds.json` file. 

```
php /path/to/post_to_instapaper/add_to_instapaper.php
```

## Example feed JSON 

**feeds.json**
```
{
    "feeds": [
        {
            "uuid": 1,
            "rss_url": "https//domain1.com/rss"
        },
        {
            "uuid": 2,
            "rss_url": "https//domain2.com/rss"
        },
        {
            "uuid": 3,
            "rss_url": "https//domain3.com/rss"
        }
    ],
    "last_updated": ""
}
```

You can then set up a crontab to run this script using the following format: 
```
# Run the script hourly
`0 * * * * php /path/to/post_to_instapaper/add_to_instapaper.php > /dev/null 2>&1
```