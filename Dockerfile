FROM ubuntu

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y ffmpeg pip git php-cli sed jq pipx

RUN pipx ensurepath

RUN pipx install whisper-ctranslate2

## RUN whisper-ctranslate2

WORKDIR /

COPY ui/ ui/
RUN mkdir -p ui/jobs/completed ui/jobs/pending

RUN sed -i 's/upload_max_filesize\s*=.*/upload_max_filesize=1024M/g'    /etc/php/8.3/cli/php.ini && \
    sed -i 's/post_max_size\s*=.*/post_max_size=1024M/g'                /etc/php/8.3/cli/php.ini

COPY wrapper.sh /wrapper.sh

CMD /wrapper.sh

# docker run --volume ./cache:/root/.cache/whisper --rm --name whisper -p 8000:8000 whisper-full:latest 

