#!/usr/bin/env bash

function update() {
    local FILE=${1}
    local FIELD=${2}
    local VALUE=${3}
    local TMP=$(mktemp)
    echo "Updating ${FIELD} of ${FILE} to ${VALUE}"
    jq --arg value "${VALUE}" ".$FIELD = \$value" "${FILE}" >"${TMP}"
    mv "${TMP}" "${FILE}"
}

function get-duration() {
    local FILE=${1}
    ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "${FILE}"
}

for FILE in ./jobs/pending/*.json; do
    if [ -f "${FILE}" ]; then
        echo "Processing ${FILE} file..."
        AUDIO_FILE=$(jq -r ".audio_file" "${FILE}")
        LANGUAGE=$(jq -r ".language" "${FILE}")
        MODEL=$(jq -r ".model" "${FILE}")

        update "${FILE}" "transcription_status" "procesando"
        update "${FILE}" "transcription_start_date" "$(date '+%Y-%m-%d %H:%M:%S')"

        ## whisper --model "${MODEL}" --output_dir /ui/jobs/pending --output_format txt --task transcribe --language "${LANGUAGE}" --verbose True --fp16 False "/ui/jobs/pending/${AUDIO_FILE}"
        whisper-ctranslate2 --model "${MODEL}" --output_dir /ui/jobs/pending --output_format txt --task transcribe --language "${LANGUAGE}" --verbose True --compute_type int8 --vad_filter True --model_dir /root/.cache/whisper "/ui/jobs/pending/${AUDIO_FILE}"
        TRANSCRIPTION_FILE="${AUDIO_FILE%.*}.txt"

        update "${FILE}" "transcription_status" "completado"
        update "${FILE}" "transcription_finish_date" "$(date '+%Y-%m-%d %H:%M:%S')"
        update "${FILE}" "transcription_file" "${TRANSCRIPTION_FILE}"

        mv "jobs/pending/${AUDIO_FILE}" ./jobs/completed
        mv "${FILE}" ./jobs/completed
        mv "jobs/pending/${TRANSCRIPTION_FILE}" ./jobs/completed
        echo "Done processing ${FILE} file"
    else
        echo "No files found"
    fi
done
