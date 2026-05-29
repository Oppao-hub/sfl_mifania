import { Controller } from '@hotwired/stimulus';
import { io } from 'socket.io-client';
import * as Turbo from '@hotwired/turbo';

export default class extends Controller {
    static values = {
        userId: String,
        socketUrl: { type: String, default: "" }
    }

    connect() {
        if (!this.userIdValue) return;
        this.reloadTimeout = null;

        this.socket = io(this.socketUrlValue, {
            path: '/socket.io',
            auth: { userId: this.userIdValue, token: this.userIdValue },
            transports: ['websocket', 'polling'],
            reconnection: true,
        });

        this.socket.on("connect", () => {
            console.log("Connected to notification server", this.socketUrlValue);
        });

        this.socket.on("connect_error", (err) => {
            console.warn("Socket connection error:", err.message, this.socketUrlValue);
        });

        // Listen for generic notification events
        this.socket.on("notification", (data) => {
            this.showNotification(data);
            this.handleRealtimeRefresh({
                entity: data?.type || 'system',
                orderId: data?.orderId,
                action: 'changed',
            });
        });

        // Specific legacy event handling if needed
        this.socket.on("new_order", (data) => {
            this.showNotification({
                title: "New Order",
                message: `Order #${data.orderId} received!`,
                icon: data.icon || null
            });
            this.handleRealtimeRefresh({ entity: 'order', orderId: data.orderId });
        });

        this.socket.on("order_status_update", (data) => {
            this.handleRealtimeRefresh({ entity: 'order', orderId: data.orderId });
        });

        this.socket.on("dashboard_refresh", (data) => {
            this.handleRealtimeRefresh(data);
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

        // 1. Try Browser Notification (Local Notification)
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, options);
        } else {
            // 2. Fallback to a UI toast or alert if permission not granted
            console.info("Notification:", title, options.body);
            
            // Dispatch a global event for Alpine.js or other listeners
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
        window.dispatchEvent(new CustomEvent('realtime:refresh', { detail: data }));

        if (this.reloadTimeout) {
            clearTimeout(this.reloadTimeout);
        }

        this.reloadTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('_rt', String(Date.now()));

            try {
                if (Turbo.cache?.clear) {
                    Turbo.cache.clear();
                }
                Turbo.visit(url.toString(), { action: 'replace' });
            } catch (error) {
                console.warn('Turbo refresh failed, falling back to reload.', error);
                window.location.assign(url.toString());
            }
        }, 400);
    }

    disconnect() {
        if (this.reloadTimeout) {
            clearTimeout(this.reloadTimeout);
        }
        if (this.socket) {
            this.socket.disconnect();
        }
    }
}
