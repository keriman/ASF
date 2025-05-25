const express = require('express');
const path = require('path');
const app = express();

app.use(express.static(path.join(__dirname, '.')));

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'warehouse.html'));
});

app.get('/production', (req, res) => {
    res.sendFile(path.join(__dirname, 'production.html'));
});

app.listen(8081, () => {
    console.log('Static file server running on http://localhost');
});
