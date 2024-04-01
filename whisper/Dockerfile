FROM ubuntu

RUN apt-get update && \
    apt-get install -y ffmpeg pip git

RUN pip install git+https://github.com/openai/whisper.git 

WORKDIR /data

ENTRYPOINT ["whisper"]

# example: docker run -it -v ./cache:/root/.cache/whisper -v ./data:/data whisper:latest --model small --output_dir /data --output_format txt --task transcribe --language es --verbose True  /data/test.mp3