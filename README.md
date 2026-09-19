# arador-dayn.fr

Archive legacy phpbb in a container.

## Run

Use `ghcr.io/fljdin/arador-dayn.fr:latest` or build it locally:

```sh
docker build --tag local/arador-dayn:5.6-alpine .
docker run -d -p 8080:8080 \
    -v ./volumes/data:/data \
    -v ./volumes/gallery:/var/www/images/avatars/gallery \
    -v ./volumes/upload:/var/www/images/avatars/upload \
    --env-file .env \
    --name arador-dayn local/arador-dayn:5.6-alpine
```

## Brevo API

Use your Brevo API key as an environment variable.

```sh
BREVO_API_KEY=keyxxx…
```

Change mailing function in panel control to `brevo_mail`.

Test custom `brevo_mail` function:

```sh
BREVO_TEST_EMAIL=your_email php includes/brevo/test_brevo.php
```
