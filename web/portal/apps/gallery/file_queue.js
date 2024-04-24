// buffer file upload to backend

// custom MetaFile object to contain file data AND extra metadata (ie. dimensions)
class MetaFile {
    #file;
    #width = 0;
    #height = 0;
    #album;

    constructor(file, width, height, album) {
        this.#file = file;
        this.#width = width;
        this.#height = height;
        this.#album = album;
    }

    get file() {  return this.#file;  }
    get width() {  return this.#width;  }
    get height() {  return this.#height;  }
    get album() {  return this.#album;  }

    // passthroughs
    get name() {  return this.#file.name;  }
    get lastModified() {  return this.#file.lastModified;  }
    get size() {  return this.#file.size;  }
};

// custom queue to allow MetaFile objects
class FileQueue {
    #items;
    #currentPromise = null;

    constructor() {  this.#items = [];  }
    
    get size() {  return this.#items.length;  }
    get length() {  return this.#items.length;  }
    empty() {  return this.length === 0;  }
    front() {  return this.#items[0];  };

    // Start file uploading. Returns true if started, false if already running.
    start() {
        // if not currently awaiting a promise, start with this item
        if (this.#currentPromise === null) {
            this.#processNext();
            return true;
        }
        return false;
    }

    // push/enqueue a single item
    enqueue(data) {
        if (data === null || data === undefined || data.constructor !== MetaFile)
            throw new Error("Invalid argument passed to FileQueue.enqueue: expected MetaFile.");

        this.#items.push(data);
    }

    // remove first element
    dequeue() {
        if (this.#items.length === 0) return;
        
        // notify user
        const data = this.#items.shift();
        passivePrompt(`Uploaded: "${data.name}" (${this.size} remaining)`, true);

        // if empty, focus new album
        if (this.empty())
            location.reload(false); // focus first page of album
        
        return data;
    }

    // private method, process the next (first) item
    #processNext() {
        // prevent running if currentPromise is not null
        if (this.#currentPromise !== null) return;

        // if empty, return
        if (this.empty()) {
            this.#currentPromise = null;
            return;
        }

        // upload next file
        this.#currentPromise = new Promise((res, rej) => {
            // upload first item
            const data = this.front();
            const payload = new FormData();

            // append form data
            payload.append("user-media[]", data.file);
            payload.append("album-name", data.album);
            payload.append("timestamps", JSON.stringify([data.lastModified]));
            payload.append("dimensions", JSON.stringify([[data.width, data.height]]));

            // make ajax call
            $.ajax({
                "url": "uploadFile.php",
                "method": "POST",
                "data": payload,
                "contentType": false,
                "processData": false,
                "success": res,
                "error": rej
            });
        }).then(() => {
            // pop, then keep going
            this.#currentPromise = null;
            this.dequeue();
            this.#processNext();
        }).catch((e) => {
            handleError(e);

            // keep going even if one file fails
            this.#currentPromise = null;
            this.dequeue();
        });
    }
};