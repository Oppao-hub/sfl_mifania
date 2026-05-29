const express = require('express');
const app = express();

app.use(express.json());

// Express must own the HTTP server so /publish is reachable from Symfony.
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    path: '/socket.io',
    cors: {
        origin: [
            "https://sfl-mifania.up.railway.app",
            "https://web-socket-production-29ca.up.railway.app",
            "http://localhost:8000",
            "http://127.0.0.1:8000",
            "http://localhost:3001",
            "http://127.0.0.1:3001",
            "http://10.0.2.2:8000",
        ],
        methods: ["GET", "POST"],
    },
});

// 1. Authentication — room key must match Symfony publish(userId) and web dashboard
io.use((socket, next) => {
    const userId = socket.handshake.auth?.userId ?? socket.handshake.auth?.token ?? 'guest';
    socket.userId = String(userId);
    next();
});

io.on('connection', (socket) => {
    socket.join(`user_${socket.userId}`);
    // Catalog/stock/product updates reach every connected client (mobile, storefront, dashboard).
    socket.join('catalog');
    console.log(`User ${socket.userId} connected`);
    socket.on('disconnect', () => {
        console.log(`User ${socket.userId} disconnected`);
    });
});

// 2. Internal API for Symfony
app.post('/publish', (req, res) => {
    const { userId, room, event, data } = req.body;
    if (!event || data == null) {
        return res.status(400).send('Missing required fields');
    }

    if (room) {
        io.to(String(room)).emit(event, data);
        console.log(`[publish] room=${room} event=${event}`);
        return res.sendStatus(200);
    }

    if (!userId) {
        return res.status(400).send('Missing userId or room');
    }

    io.to(`user_${userId}`).emit(event, data);
    console.log(`[publish] user_${userId} event=${event}`);
    res.sendStatus(200);
});

const PORT = process.env.PORT || process.env.SOCKET_PORT || 3001;
const HOST = process.env.SOCKET_HOST || '0.0.0.0';
http.listen(PORT, HOST, () => {
  console.log(`Socket.IO server running on ${HOST}:${PORT}`);
});
