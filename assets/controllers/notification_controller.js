import { Controller } from '@hotwired/stimulus';
import { io } from 'socket.io-client';

export default class extends Controller {
    static values = {
        userId: String,
        socketUrl: { type: String, default: "" }
    }

    connect() {
        if (!this.userIdValue || !this.socketUrlValue) return;

        this.reloadTimeout = null;
        this.connectSocket();
        this.onVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                if (!this.socket?.connected) {
                    this.connectSocket();
                }
            }
        };
        document.addEventListener('visibilitychange', this.onVisibilityChange);
    }

    connectSocket() {
        if (this.socket) {
            this.socket.removeAllListeners();
            this.socket.disconnect();
        }

        this.socket = io(this.socketUrlValue, {
            path: '/socket.io',
            auth: { userId: this.userIdValue, token: this.userIdValue },
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionAttempts: Infinity,
        });

        this.socket.on("connect", () => {
            console.info("[realtime] connected", this.socketUrlValue, "room user_" + this.userIdValue);
        });

        this.socket.on("connect_error", (err) => {
            console.warn("[realtime] connection error:", err.message, this.socketUrlValue);
        });

        const refresh = (data = {}) => this.handleRealtimeRefresh(data);

        this.socket.on("notification", (data) => {
            this.showNotification(data);
            refresh({
                entity: data?.type || 'system',
                orderId: data?.orderId,
                action: 'changed',
            });
        });

        this.socket.on("new_order", (data) => {
            this.showNotification({
                title: "New Order",
                message: `Order #${data.orderId} received!`,
                icon: data.icon || null
            });
            refresh({ entity: 'order', orderId: data.orderId, action: 'created' });
        });

        this.socket.on("order_status_update", (data) => {
            refresh({ entity: 'order', orderId: data.orderId, action: 'updated' });
        });

        this.socket.on("dashboard_refresh", (data) => {
            refresh(data);
        });

        this.requestPermission();
    }

    requestPermission() {
        if ("Notification" in window) {
            if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                Notification.requestPermission();
            }
        }
    }

    showNotification(data) {
        const title = data.title || "New Notification";
        const options = {
            body: data.message || "",
            icon: data.icon || "/favicon.ico",
        };

        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, options);
        } else {
            console.info("Notification:", title, options.body);
            window.dispatchEvent(new CustomEvent("notify", {
                detail: {
                    title: title,
                    message: data.message,
                    type: data.type || 'system',
                    targetUrl: data.targetUrl || '#'
                }
            }));
        }
    }

    handleRealtimeRefresh(data = {}) {
        console.info("[realtime] refresh", data);
        window.dispatchEvent(new CustomEvent('realtime:refresh', { detail: data }));

        if (this.reloadTimeout) {
            clearTimeout(this.reloadTimeout);
        }

        this.reloadTimeout = setTimeout(() => {
            // Hard reload is more reliable than Turbo for server-rendered dashboard tables.
            window.location.reload();
        }, 300);
    }

    disconnect() {
        document.removeEventListener('visibilitychange', this.onVisibilityChange);
        if (this.reloadTimeout) {
            clearTimeout(this.reloadTimeout);
        }
        if (this.socket) {
            this.socket.disconnect();
        }
    }
}
