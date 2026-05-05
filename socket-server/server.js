const http = require('http').createServer();
const io = require('socket.io')(http, {    cors: { origin: "*" } });const express = require('express');
const app = express();app.use(express.json())// 1. Authentication Middleware
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    if (!token) return next(new Error("Authentication error"));

    // In production, verify the JWT from Symfony
    // For now, we assume token is the userId for testing
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
        console.error('Invalid publish request:', req.body);
        return res.status(400).send('Missing required fields');
    }

    console.log(`Publishing event "${event}" to user ${userId}`);
    // Emit to a specific user's room
    io.to(`user_${userId}`).emit(event, data);
    res.sendStatus(200);
});

// Run Socket.io on 3001, Internal API on 3000
http.listen(3001, () => console.log('Socket.io running on port 3001'));
app.listen(3000, () => console.log('Internal API running on port 3000'));
