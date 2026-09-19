# arador-dayn.fr

Archive legacy phpbb in a container.

## Run

```sh
docker build --tag local/arador-dayn:5.6-alpine .
docker run -d -p 8080:8080 \
    -v ./volumes/data:/data \
    -v ./volumes/gallery:/var/www/images/avatars/gallery \
    -v ./volumes/upload:/var/www/images/avatars/upload \
    --name arador-dayn local/arador-dayn:5.6-alpine
```
