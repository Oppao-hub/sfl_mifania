const http = require('http').createServer();
const io = require('socket.io')(http, {
    path: '/socket.io',
    cors: {
        origin: ["https://sflmifania-production.up.railway.app", "http://localhost:8000"],
        methods: ["GET", "POST"]
    }
});
const express = require('express');
const app = express();

app.use(express.json());

// 1. Authentication Middleware
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    if (!token) return next(new Error("Authentication error"));
    socket.userId = token;
    next();
});

io.on('connection', (socket) => {
    socket.join(`user_${socket.userId}`);
    console.log(`User ${socket.userId} connected`);
    socket.on('disconnect', () => {
        console.log(`User ${socket.userId} disconnected`);
    });
});

// 2. Internal API for Symfony
app.post('/publish', (req, res) => {
    const { userId, event, data } = req.body;
    if (!userId || !event || !data) {
        return res.status(400).send('Missing required fields');
    }
    io.to(`user_${userId}`).emit(event, data);
    res.sendStatus(200);
});

const PORT = 3001;
http.listen(PORT, '127.0.0.1', () => {
  console.log(`Socket.IO server running internally on port ${PORT}`);
});
