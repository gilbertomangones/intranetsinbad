ARG MYSQL_VERSION=8.0
FROM mysql:${MYSQL_VERSION}

ARG MYSQL_TIMEZONE=UTC
RUN ln -snf /usr/share/zoneinfo/$MYSQL_TIMEZONE /etc/localtime && echo $MYSQL_TIMEZONE > /etc/timezone && chown -R mysql:root /var/lib/mysql/

COPY my.cnf /etc/mysql/conf.d/my.cnf

RUN chmod 0444 /etc/mysql/conf.d/my.cnf

CMD ["mysqld"]

EXPOSE 3306
