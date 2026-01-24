// =================== INDEXEDDB SETUP ===================
const dbName = appName || "ApiTesterDB";
const db = new Dexie(dbName);
db.version(1).stores({
    history: "++id, time"  // auto-incrementing id as primary key, index on time for sorting/queries
});
// Global in-memory array (starts empty)
let HISTORY = [];
db.open().catch(err => {
    console.error("Failed to open DB:", err);
});
