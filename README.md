[![Docker Automated buil](https://img.shields.io/docker/automated/agentsib/siteshot.svg)](https://hub.docker.com/r/agentsib/siteshot/)

# Screenshot sites tool 

Web service to generate images of websites. Written with Silex framework. Uses [wkhtmltopdf](http://wkhtmltopdf.org/).

## Running

```bash
docker run -d -p 8080:80 -v /tmp/siteshot_cache:/var/www/shot/cache agentsib/siteshot:latest
```

Now try [localhost:8080](http://localhost:8080/)

## Usage

`http://localhost:8080/{mode}/{sizes}/{fwidth}/{format}/t{timeout}?{url}`

* mode - `corner` or `resize`
* sizes - screenshot size (for example 400 or 400x500)
* fwidth - for `corner` - crop upper left corner box width and height, for `resize` - max width
* format - `png` or `jpg`
* timeout - wait time after load page content (by default: 1). Usable for sites with flash. Example: t1 or t10
* url - url for capture

Returns http status 404 if creating screenshot failed.

------

### Examples

http://siteshot.dev/resize/800x600/400/png?http://google.com

http://siteshot.dev/resize/800x600/400/png/t5?http://speedtest.net

http://siteshot.dev/corner/800x600/400/png?http://vk.com

## Development

Show symfony errors:

```bash
docker run -ti -p 8080:80 -e DEBUG=1 -v /path/to/siteshot-php:/var/www/shot agentsib/siteshot:latest
```
