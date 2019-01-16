IMAGE = microparts/static-server-php
VERSION = latest

image:
	docker build -t $(IMAGE):$(VERSION) .

run:
	docker run --rm -it -p 8088:8080 $(IMAGE):$(VERSION)
