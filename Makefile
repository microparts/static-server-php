IMAGE = microparts/static-server-php
VERSION = latest
FILE = Dockerfile

image:
	docker build -f $(FILE) -t $(IMAGE):$(VERSION) .

push:
	docker push $(IMAGE):$(VERSION)

run:
	docker run --rm -it -p 8088:8080 $(IMAGE):$(VERSION)
