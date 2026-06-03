package net.centralsystem.financiera;

import android.content.Context;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.webkit.JavascriptInterface;

import org.json.JSONArray;
import org.json.JSONObject;

class OfflineBridge {
    private final Context context;
    private final OfflineDatabase database;

    OfflineBridge(Context context, OfflineDatabase database) {
        this.context = context.getApplicationContext();
        this.database = database;
    }

    @JavascriptInterface
    public boolean isOnline() {
        ConnectivityManager manager = (ConnectivityManager) context.getSystemService(Context.CONNECTIVITY_SERVICE);
        NetworkInfo info = manager == null ? null : manager.getActiveNetworkInfo();
        return info != null && info.isConnected();
    }

    @JavascriptInterface
    public String getAuthToken() {
        String token = database.getValue("mobile_auth_token");
        return token == null ? "" : token;
    }

    @JavascriptInterface
    public void setAuthToken(String token) {
        database.putValue("mobile_auth_token", token == null ? "" : token);
    }

    @JavascriptInterface
    public String getBootstrapSnapshot() {
        String snapshot = database.getValue("bootstrap_snapshot");
        return snapshot == null ? "{}" : snapshot;
    }

    @JavascriptInterface
    public void setBootstrapSnapshot(String json) {
        database.putValue("bootstrap_snapshot", json == null ? "{}" : json);
    }

    @JavascriptInterface
    public String queueOperation(String type, String payloadJson) {
        try {
            String id = database.enqueue(type, payloadJson == null ? "{}" : payloadJson);
            JSONObject response = new JSONObject();
            response.put("ok", true);
            response.put("client_operation_id", id);
            response.put("pending_count", database.pendingCount());
            return response.toString();
        } catch (Exception exception) {
            return errorJson(exception.getMessage());
        }
    }

    @JavascriptInterface
    public String getPendingOperations() {
        try {
            JSONObject response = new JSONObject();
            response.put("ok", true);
            response.put("operations", new JSONArray(database.pendingOperationsJson()));
            response.put("pending_count", database.pendingCount());
            return response.toString();
        } catch (Exception exception) {
            return errorJson(exception.getMessage());
        }
    }

    @JavascriptInterface
    public void markOperationProcessed(String id) {
        database.markProcessed(id);
    }

    @JavascriptInterface
    public void markOperationFailed(String id, String error) {
        database.markFailed(id, error);
    }

    private String errorJson(String message) {
        try {
            JSONObject response = new JSONObject();
            response.put("ok", false);
            response.put("error", message == null ? "Error local." : message);
            return response.toString();
        } catch (Exception ignored) {
            return "{\"ok\":false,\"error\":\"Error local.\"}";
        }
    }
}
