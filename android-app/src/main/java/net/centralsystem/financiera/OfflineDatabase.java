package net.centralsystem.financiera;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.UUID;

class OfflineDatabase extends SQLiteOpenHelper {
    private static final String DB_NAME = "financiera_offline.db";
    private static final int DB_VERSION = 1;

    OfflineDatabase(Context context) {
        super(context, DB_NAME, null, DB_VERSION);
    }

    @Override
    public void onCreate(SQLiteDatabase db) {
        db.execSQL("CREATE TABLE kv_store (key TEXT PRIMARY KEY, value TEXT, updated_at INTEGER NOT NULL)");
        db.execSQL("CREATE TABLE sync_queue (" +
                "id TEXT PRIMARY KEY, " +
                "type TEXT NOT NULL, " +
                "payload TEXT NOT NULL, " +
                "status TEXT NOT NULL DEFAULT 'pending', " +
                "error TEXT, " +
                "created_at INTEGER NOT NULL, " +
                "updated_at INTEGER NOT NULL)");
    }

    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        // Keep user data. Future migrations should alter tables in place.
    }

    void putValue(String key, String value) {
        long now = System.currentTimeMillis();
        ContentValues values = new ContentValues();
        values.put("key", key);
        values.put("value", value);
        values.put("updated_at", now);
        getWritableDatabase().insertWithOnConflict("kv_store", null, values, SQLiteDatabase.CONFLICT_REPLACE);
    }

    String getValue(String key) {
        try (Cursor cursor = getReadableDatabase().query(
                "kv_store",
                new String[]{"value"},
                "key = ?",
                new String[]{key},
                null,
                null,
                null,
                "1")) {
            if (cursor.moveToFirst()) {
                return cursor.getString(0);
            }
        }
        return null;
    }

    String enqueue(String type, String payloadJson) throws Exception {
        new JSONObject(payloadJson);

        long now = System.currentTimeMillis();
        String id = UUID.randomUUID().toString();

        ContentValues values = new ContentValues();
        values.put("id", id);
        values.put("type", type);
        values.put("payload", payloadJson);
        values.put("status", "pending");
        values.put("created_at", now);
        values.put("updated_at", now);
        getWritableDatabase().insertOrThrow("sync_queue", null, values);
        return id;
    }

    String pendingOperationsJson() throws Exception {
        JSONArray operations = new JSONArray();

        try (Cursor cursor = getReadableDatabase().query(
                "sync_queue",
                new String[]{"id", "type", "payload", "status", "error", "created_at", "updated_at"},
                "status IN (?, ?)",
                new String[]{"pending", "failed"},
                null,
                null,
                "created_at ASC")) {
            while (cursor.moveToNext()) {
                JSONObject operation = new JSONObject();
                operation.put("client_operation_id", cursor.getString(0));
                operation.put("type", cursor.getString(1));
                operation.put("payload", new JSONObject(cursor.getString(2)));
                operation.put("status", cursor.getString(3));
                operation.put("error", cursor.isNull(4) ? JSONObject.NULL : cursor.getString(4));
                operation.put("created_at", cursor.getLong(5));
                operation.put("updated_at", cursor.getLong(6));
                operations.put(operation);
            }
        }

        return operations.toString();
    }

    int pendingCount() {
        try (Cursor cursor = getReadableDatabase().rawQuery(
                "SELECT COUNT(*) FROM sync_queue WHERE status IN ('pending', 'failed')",
                null)) {
            return cursor.moveToFirst() ? cursor.getInt(0) : 0;
        }
    }

    void markProcessed(String id) {
        updateStatus(id, "processed", null);
    }

    void markFailed(String id, String error) {
        updateStatus(id, "failed", error);
    }

    private void updateStatus(String id, String status, String error) {
        ContentValues values = new ContentValues();
        values.put("status", status);
        values.put("error", error);
        values.put("updated_at", System.currentTimeMillis());
        getWritableDatabase().update("sync_queue", values, "id = ?", new String[]{id});
    }
}
