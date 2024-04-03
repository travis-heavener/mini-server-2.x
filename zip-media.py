from datetime import datetime as dt
import shutil
from sys import argv

if __name__ == "__main__":
    # zips all media in the media folder into a zip archive
    # for transport between development devices
    DEST = dt.now().strftime("./media-%m-%d-%y_%H-%M-%S")
    SRC = "./media/"
    
    shutil.make_archive(DEST, "zip", SRC)
    print("Successfully backed up media to:", DEST + ".zip")