FROM ubuntu:16.04
MAINTAINER Ilya Kovalenko <agentsib@gmail.com>

RUN echo 'deb http://archive.ubuntu.com/ubuntu/ xenial-security multiverse' >> /etc/apt/sources.list && \
    echo 'deb-src http://archive.ubuntu.com/ubuntu/ xenial-security multiverse' >> /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y wget wkhtmltopdf vim cabextract libmspack0 xfonts-75dpi xvfb flashplugin-installer xz-utils supervisor \
    php php-fpm php-imagick libapache2-mod-php apache2 libapache2-mod-rpaf libapache2-mod-xsendfile && \
    apt-get -y remove wkhtmltopdf && \
    wget http://ftp.de.debian.org/debian/pool/contrib/m/msttcorefonts/ttf-mscorefonts-installer_3.6_all.deb && \
    dpkg -i ttf-mscorefonts-installer_3.6_all.deb && \
    rm ttf-mscorefonts-installer_3.6_all.deb && \
    # SYSTEM
    apt-get install wkhtmltopdf && \
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
ADD vendor /var/www/shot/vendor

ENV DISPLAY :99

EXPOSE 80

VOLUME /var/www/shot/cache

ENTRYPOINT ["/entrypoint.sh"]

CMD ["/usr/bin/supervisord"]