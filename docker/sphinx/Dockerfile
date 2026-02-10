# Dockerfile for Sphinx SE
# https://hub.docker.com/_/alpine/
FROM ubuntu:24.04

# https://sphinxsearch.com/blog/
ENV SPHINX_VERSION 3.7.1-da9f8a4

# install dependencies
RUN DEBIAN_FRONTEND=noninteractive apt-get update && apt-get install -y \
	wget

# set up and expose directories
RUN mkdir -pv /opt/sphinx/logs /opt/sphinx/indexes
VOLUME /opt/sphinx/indexes

# https://sphinxsearch.com/files/sphinx-3.7.1-da9f8a4-linux-amd64.tar.gz
RUN set -eux; \
    ARCH=$(uname -m); \
    if [ "$ARCH" = "x86_64" ] ; then export ARCH=amd64; fi; \
    wget http://sphinxsearch.com/files/sphinx-${SPHINX_VERSION}-linux-${ARCH}.tar.gz -O /tmp/sphinxsearch.tar.gz; \
	cd /opt/sphinx && tar -xf /tmp/sphinxsearch.tar.gz; \
	rm /tmp/sphinxsearch.tar.gz

# point to sphinx binaries
ENV PATH "${PATH}:/opt/sphinx/sphinx-3.7.1/bin"
RUN ls -la /opt/sphinx/sphinx-3.7.1/bin
RUN ls -l /opt/sphinx/sphinx-3.7.1/bin/indexer -v
RUN /opt/sphinx/sphinx-3.7.1/bin/indexer -v

# redirect logs to stdout
RUN ln -sv /dev/stdout /opt/sphinx/logs/query.log \
    	&& ln -sv /dev/stdout /opt/sphinx/logs/searchd.log

# allow custom config file to be passed
COPY sphinx.conf /opt/sphinx/conf/sphinx.conf
COPY stopwords.txt /opt/sphinx/extra/stopwords.txt

# prepare a start script
RUN echo "exec searchd --nodetach --config /opt/sphinx/conf/sphinx.conf" > /opt/sphinx/start.sh
RUN chmod +x /opt/sphinx/start.sh

ENTRYPOINT /opt/sphinx/start.sh

VOLUME /opt/sphinx/indexes