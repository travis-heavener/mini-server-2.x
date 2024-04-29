import json
import os
import subprocess

from consts import *

# gets the runtime of an mkv file
# adapted from https://stackoverflow.com/questions/3844430/how-to-get-the-duration-of-a-video-in-python
def get_mkv_runtime(filename):
    result = subprocess.check_output(
            f'ffprobe -v quiet -show_streams -select_streams v:0 -of json "{filename}"',
            shell=True).decode()
    fields = json.loads(result)['streams'][0]

    parts = fields['tags']['DURATION'].split(":")
    dur = int(parts[0]) * 60 + int(parts[1]) + float(parts[2]) / 60
    return round(dur)

def hls_gen(MKV_PATH, keep_mkv):
    CONTENT_DIR = os.path.dirname(MKV_PATH)
    print(CONTENT_DIR)

    print("Starting FFMPEG HLS conversion...")
    process = subprocess.Popen(f"cd \"{CONTENT_DIR}\" && ffmpeg -hide_banner -loglevel warning -i \"{MKV_PATH}\" \
                               -c:v libx264 -crf {STREAM_QUALITY} -r {STREAM_FRAME_RATE} -hls_time {STREAM_SEG_DUR} \
                                -hls_list_size 0 stream.m3u8", shell=True)
    process.wait()

    print("FFMPEG HLS stream created.")

    # extract film metadata
    title = input("Enter film title: ")
    year = input("Enter film year: ")
    runtime = get_mkv_runtime(MKV_PATH)
    thumb_url = input("Enter thumbnail image URL: ")

    # remove the temp mkv file
    if not keep_mkv: os.remove(MKV_PATH)

    return title, year, runtime, thumb_url