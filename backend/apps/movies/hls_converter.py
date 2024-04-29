from pymediainfo import MediaInfo
import os
import subprocess

from consts import *

# gets the runtime of a video file
def get_runtime_ms(path):
    info = MediaInfo.parse(path)
    return info.tracks[0].duration

def hls_gen(SRC_PATH, keep_mkv, CONTENT_DIR=None, skip_thumb=False):
    CONTENT_DIR = CONTENT_DIR if CONTENT_DIR != None else os.path.dirname(SRC_PATH)

    print("Starting FFMPEG HLS conversion...")
    process = subprocess.Popen(f"cd \"{CONTENT_DIR}\" && ffmpeg -hide_banner -loglevel warning -i \"{SRC_PATH}\" \
                               -c:v libx264 -crf {STREAM_QUALITY} -r {STREAM_FRAME_RATE} -hls_time {STREAM_SEG_DUR} \
                                -hls_list_size 0 stream.m3u8", shell=True)
    process.wait()

    print("FFMPEG HLS stream created.")

    # extract film metadata
    title = input("Enter film title: ")
    year = input("Enter film year: ")
    runtime = round(get_runtime_ms(SRC_PATH) / 6e4) # ms -> min
    thumb_url = input("Enter thumbnail image URL: ") if not skip_thumb else None

    # remove the temp mkv file
    if not keep_mkv: os.remove(SRC_PATH)

    return title, year, runtime, thumb_url