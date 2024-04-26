from datetime import datetime
from dotenv import load_dotenv
import mysql.connector
import os
from os.path import dirname, exists, join
from pathlib import Path

# logger
LOG_PATH = ""
def log(msg):
    # verify LOG_PATH has been determined
    if LOG_PATH == "":
        print(msg)
        return
    
    # verify folder exists
    if not exists(dirname(LOG_PATH)):
        Path(dirname(LOG_PATH)).mkdir(parents=True, exist_ok=True)

    # verify file exists
    if not exists(LOG_PATH):
        f = open(LOG_PATH, "x")
    else:
        f = open(LOG_PATH, "a")
    
    # write to file
    f.write(msg + "\n")
    f.close()

# clean out all recycle bin content
def main():
    # load envs
    env_path = join(dirname(__file__), "../../../config/.env")
    load_dotenv(env_path, override=True)

    # generate log path
    global LOG_PATH
    now_str = datetime.now().strftime("%m-%d-%Y_%H-%M-%S.log")
    LOG_PATH = join(dirname(os.getenv("LOG_PATH")), "apps", "gallery", now_str)

    log("Starting Gallery App Recycle Bin audit...")

    # establish connection
    try:
        db = mysql.connector.connect(
            host=os.getenv("HOST"), user=os.getenv("USER"),
            password=os.getenv("PASS"), database=os.getenv("DBID")
        )
    except mysql.connector.errors.DatabaseError:
        log("Failed to connect to database.")
        exit(1)

    log("MySQL connection established.")
    cursor = db.cursor()
    
    # grab all tables starting with gallery prefix
    log("Fetching all Gallery App tables...")

    prefix = "gallery__"
    cursor.execute(f"SELECT `table_name` FROM information_schema.tables WHERE `table_schema`=\"{os.getenv('DBID')}\" AND `table_name` LIKE \"{prefix}%\";")
    tables = [row[0] for row in cursor.fetchall()]

    log(f"Found {len(tables)} table(s).")

    # execute query on each table
    media_path = os.getenv("GALLERY_PATH")
    for table in tables:
        user_id_hex = table.replace(prefix, "")

        # grab all outdated entries
        cursor.execute(f"SELECT `id` FROM `{table}` WHERE `deletion_date` <= CURRENT_TIMESTAMP;")
        ids = tuple([row[0] for row in cursor.fetchall()])
        
        # remove all with matching ids
        if len(ids) == 0:
            log(f"    [{table}]: All good.")
            continue

        cursor.execute(f"DELETE FROM `{table}` WHERE `id` IN ({','.join([str(id) for id in ids])}) LIMIT 1;")
        db.commit()

        # remove all matching files from media folder
        for id in ids:
            res_path = join(dirname(media_path), user_id_hex + "_" + hex(id)[2:] + ".bin")
            thumb_path = res_path.replace(".bin", "T.bin")
            if exists(res_path): os.remove(res_path)
            if exists(thumb_path): os.remove(thumb_path)

        log(f"    [{table}]: Removed {len(ids)} row(s) and associated files.")
    
    # log completion
    log("Gallery App Recycle Bin audit complete.")

if __name__ == "__main__":
    try:
        main()
        exit(0)
    except mysql.connector.errors.DatabaseError:
        log("A MySQL connection error occured.")
        exit(1)