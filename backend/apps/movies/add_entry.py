from dotenv import load_dotenv
import glob
import json
import mysql.connector
import os
from os.path import dirname, exists, join
from pathlib import Path
import subprocess
from sys import argv

from consts import *
from hls_converter import *

def main(DISC_DEVICE_NAME, DISC_TITLES, keep_mkv):
    # 1. load envs
    env_path = join(dirname(__file__), "../../../config/.env")
    load_dotenv(env_path, override=True)

    FILMS_PATH = os.getenv("MOVIES_PATH")
    
    # 2. verify MySQL is running
    try:
        db = mysql.connector.connect(
            host=os.getenv("HOST"), user=os.getenv("USER"),
            password=os.getenv("PASS"), database=os.getenv("DBID")
        )
    except mysql.connector.errors.DatabaseError:
        print("Failed to connect to database.")
        exit(1)
    
    print("MySQL connection established.")

    # 2.A extract next insert id (the id of this film)
    cursor = db.cursor(prepared=True)
    cursor.execute(f"SELECT `AUTO_INCREMENT` FROM information_schema.tables \
                   WHERE `table_schema`=\"{os.getenv('DBID')}\" AND `table_name`=\"film_library\";")
    hex_id = hex( cursor.fetchone()[0] )[2:] # remove '0x' prefix

    # 3. create stream output folder
    CONTENT_DIR = join(dirname(FILMS_PATH), hex_id)

    # verify parent folder exists
    if not exists(dirname(FILMS_PATH)):
        Path(dirname(FILMS_PATH)).mkdir(parents=True, exist_ok=True)
        print("Created new folders at MOVIES_PATH: " + FILMS_PATH)
    
    # verify movie's folder isn't in use
    if exists(CONTENT_DIR) and len(os.listdir(CONTENT_DIR)) > 0:
        print(f"Content destination folder exists and is not empty! ({{MOVIES_PATH}}/{hex_id})")
        exit(1)
    
    # base case, create new content directory
    Path(CONTENT_DIR).mkdir(exist_ok=True)

    # 4. execute script in shell to rip current DVD to content dir temporarily
    process = subprocess.Popen(f"makemkvcon mkv dev:{DISC_DEVICE_NAME} {DISC_TITLES} {CONTENT_DIR}", shell=True)
    process.wait()

    # 4.A get the newly created MKV_PATH
    files = glob.glob(join(CONTENT_DIR, "*.mkv"))
    MKV_PATH = max(files, key=os.path.getctime)
    print("Temp file created at: " + MKV_PATH)

    # 5. execute ffmpeg to take temp DVD file and break into stream in source dir
    title, year, runtime, thumb_url = hls_gen(MKV_PATH, keep_mkv)

    # 6. create thumbnail image
    process = subprocess.Popen(
        f"cd {CONTENT_DIR} && \
          ffmpeg -y -hide_banner -loglevel error -i \"{thumb_url}\" -vf \"crop='min(in_w,in_h)':'min(in_w,in_h)', \
          scale={THUMB_SIZE}:{THUMB_SIZE}\" {THUMB_NAME}",
        shell=True
    )
    process.wait()

    # 7. add film to database
    cursor.execute(
        "INSERT INTO `film_library` (`title`, `year`, `runtime`) VALUES (%s, %s, %s);",
        (title, year, runtime)
    )
    db.commit() # commit changes
    print("Content added to database.")
    db.close()

if __name__ == "__main__":
    # grab USB device name from args
    if len(argv) < 2:
        print("Invalid usage: missing \"OS Device Name\" argument for CD/ROM drive.")
        exit(1)
    
    if len(argv) < 3:
        print("Invalid usage: missing title numeric argument for DVD. Get it via MakeMKV.")
        exit(1)
    
    # allow 4th arg "--keep" to prevent deleting mkv temp file
    keep_mkv = len(argv) >= 4 and argv[3] == "--keep"

    main(argv[1], argv[2], keep_mkv)