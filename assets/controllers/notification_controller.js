import { Controller } from '@hotwired/stimulus';
import { io } from 'socket.io-client';

export default class extends Controller {
    static values = {
        userId: String,
        socketUrl: { type: String, default: "" }
    }

    connect() {
        if (!this.userIdValue) return;

        this.socket = io(this.socketUrlValue, {
            auth: { token: this.userIdValue }
        });

        this.socket.on("connect", () => {
            console.log("Connected to notification server");
        });

        // Listen for generic notification events
        this.socket.on("notification", (data) => {
            this.showNotification(data);
        });

        // Specific legacy event handling if needed
        this.socket.on("new_order", (data) => {
            this.showNotification({
                title: "New Order",
                message: `Order #${data.orderId} received!`,
                icon: data.icon || null
            });
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

    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
        }
    }
}
