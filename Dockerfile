FROM ubuntu:16.04
MAINTAINER Ilya Kovalenko <agentsib@gmail.com>

RUN echo 'deb http://archive.ubuntu.com/ubuntu/ xenial-security multiverse' >> /etc/apt/sources.list && \
    echo 'deb-src http://archive.ubuntu.com/ubuntu/ xenial-security multiverse' >> /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y wget wkhtmltopdf vim cabextract libmspack0 xfonts-75dpi xvfb flashplugin-installer xz-utils  && \
    apt-get -y remove wkhtmltopdf && \
    wget http://ftp.de.debian.org/debian/pool/contrib/m/msttcorefonts/ttf-mscorefonts-installer_3.6_all.deb && \
    dpkg -i ttf-mscorefonts-installer_3.6_all.deb && \
    rm ttf-mscorefonts-installer_3.6_all.deb && \
    # SYSTEM
    apt-get install wkhtmltopdf && \
    # LATEST
    wget http://download.gna.org/wkhtmltopdf/0.12/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    tar xfv wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    cp -R wkhtmltox/* / && \
    rm -rf wkhtmltox && \
    rm wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    # LATEST UBUNTU
#    wget http://download.gna.org/wkhtmltopdf/0.12/0.12.2/wkhtmltox-0.12.2_linux-trusty-amd64.deb && \
#    dpkg -i wkhtmltox-0.12.2_linux-trusty-amd64.deb && \
#    rm wkhtmltox-0.12.2_linux-trusty-amd64.deb && \
     apt-get clean -y && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*


ADD entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/bash"]