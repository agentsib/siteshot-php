FROM ubuntu:16.04
MAINTAINER Ilya Kovalenko <agentsib@gmail.com>

RUN echo 'deb http://archive.ubuntu.com/ubuntu/ xenial-security multiverse' >> /etc/apt/sources.list && \
    echo 'deb-src http://archive.ubuntu.com/ubuntu/ xenial-security multiverse' >> /etc/apt/sources.list && \
    echo 'deb http://archive.ubuntu.com/ubuntu/ xenial multiverse' >> /etc/apt/sources.list && \
    echo 'deb-src http://archive.ubuntu.com/ubuntu/ xenial multiverse' >> /etc/apt/sources.list && \
    apt-get update && \
    # LATEST SYSTEM UPDATES
    DEBIAN_FRONTEND=noninteractive apt-get -y --force-yes dist-upgrade && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y apt-transport-https wget vim cabextract libmspack0 xfonts-75dpi xvfb xz-utils curl supervisor git unzip \
    php php-fpm php-zip php-imagick libapache2-mod-php apache2 libapache2-mod-rpaf libapache2-mod-xsendfile && \
    # mscorefonts: license should be accepted to download fonts; apt-transport-https is used for downloads
    echo 'ttf-mscorefonts-installer msttcorefonts/accepted-mscorefonts-eula select true' | debconf-set-selections && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y ttf-mscorefonts-installer && \
    # Get wkhtmltopdf dependencies
#    apt-get -y install wkhtmltopdf && \
#    apt-get -y remove wkhtmltopdf && \
    # FLASH SUPPORT
    DEBIAN_FRONTEND=noninteractive apt-get -y install flashplugin-installer && \
    # WKHTMLTOPDF
    # SYSTEM
    DEBIAN_FRONTEND=noninteractive apt-get -y install wkhtmltopdf && \
    # LATEST
#    wget http://download.gna.org/wkhtmltopdf/0.12/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
#    tar xfv wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
#    cp -R wkhtmltox/* / && \
#    rm -rf wkhtmltox && \
#    rm wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    # STABLE
#    wget http://download.gna.org/wkhtmltopdf/0.12/0.12.3/wkhtmltox-0.12.3_linux-generic-amd64.tar.xz && \
#    tar xfv wkhtmltox-0.12.3_linux-generic-amd64.tar.xz && \
#    cp -R wkhtmltox/* / && \
#    rm -rf wkhtmltox && \
#    rm wkhtmltox-0.12.3_linux-generic-amd64.tar.xz && \
    # LATEST UBUNTU
#    wget http://download.gna.org/wkhtmltopdf/0.12/0.12.2/wkhtmltox-0.12.2_linux-trusty-amd64.deb && \
#    dpkg -i wkhtmltox-0.12.2_linux-trusty-amd64.deb && \
#    rm wkhtmltox-0.12.2_linux-trusty-amd64.deb && \
     apt-get clean -y && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
     a2enmod rewrite

ADD docker_files/entrypoint.sh /entrypoint.sh
ADD docker_files/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ADD docker_files/apache-host.conf /etc/apache2/sites-available/000-default.conf

ADD web /var/www/shot/web
ADD composer.json /var/www/shot/composer.json
ADD composer.lock /var/www/shot/composer.lock

#ADD vendor /var/www/shot/vendor
RUN chown -R www-data /var/www

USER www-data

RUN cd /var/www/shot && \
    curl -o installer https://getcomposer.org/installer && \
    php installer && \
    rm installer && \
    php composer.phar install

USER root

ENV DISPLAY :99
ENV DEBUG 0
ENV ENABLE_PLUGINS 0
ENV PROCESS_WAIT_TIME 0

EXPOSE 80

WORKDIR /var/www/shot
VOLUME /var/www/shot/cache

ENTRYPOINT ["/entrypoint.sh"]

CMD ["/usr/bin/supervisord"]
