# syntax=docker.io/docker/dockerfile:1.4@sha256:9ba7531bd80fb0a858632727cf7a112fbfd19b17e94c4e84ced81e24ef1a0dbc
FROM fluent/fluent-bit:1.9.1

COPY * /fluent-bit/etc/

ENV UPSTREAM_HOST=upstream-fluentbit.awardwallet.com
ENV UPSTREAM_PORT=24224
ENV STAT_HOST=stat-fluentbit.infra.awardwallet.com
ENV STAT_PORT=24224