from dotenv import load_dotenv
import glob
import mysql.connector
import os
from os.path import dirname, exists, join
from pathlib import Path
import subprocess
from sys import argv

from consts import *
from hls_converter import *

def main(src_path):
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

    # 4. execute ffmpeg to take temp DVD file and break into stream in source dir
    title, year, runtime = hls_gen(src_path, True, CONTENT_DIR, True)[0:3]
    runtime_sec = get_runtime_ms(src_path) / 1e3

    # 5. grab thumbnail from video via ffmepg
    max_dim = max(THUMB_WIDTH, THUMB_HEIGHT)
    min_dim = min(THUMB_WIDTH, THUMB_HEIGHT)
    process = subprocess.Popen(
        f"cd {CONTENT_DIR} && \
          ffmpeg -y -hide_banner -loglevel error -i \"{src_path}\" -vf \"crop='min(in_w,in_h)':'min(in_w,in_h)', \
          scale={max_dim}:{max_dim}, crop={THUMB_WIDTH}:{THUMB_HEIGHT}\" -vframes 1 -ss {int(runtime_sec/2)} {THUMB_NAME}",
        shell=True
    )
    process.wait()

    # 6. add film to database
    cursor.execute(
        "INSERT INTO `film_library` (`title`, `year`, `runtime`) VALUES (%s, %s, %s);",
        (title, year, runtime)
    )
    db.commit() # commit changes
    print("Content added to database.")
    db.close()

if __name__ == "__main__":
    if len(argv) < 2:
        print("Invalid usage: missing source file path.")
        exit(1)

    main(argv[1])