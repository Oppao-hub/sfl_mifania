const http = require('http').createServer();
const io = require('socket.io')(http, {
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

// Railway provides a single PORT env var. 
// We will use one port for both Express and Socket.io to simplify deployment.
const PORT = process.env.PORT || 3001;
http.on('request', app); // Attach express app to the same http server
http.listen(PORT, () => console.log(`Socket.io & API running on port ${PORT}`));
