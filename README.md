# Screenshot sites tool 

## Usage

```bash
docker run -d -p 8080:80 -v /tmp/siteshot_cache:/var/www/shot/cache agentsib/siteshot:latest
```

And try http://localhost:8080/

## Development hack


```bash
docker run -ti -p 8080:80 -v /path/to/siteshot-php:/var/www/shot agentsib/siteshot:latest

```
