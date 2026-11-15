INSERT INTO users (name, email, password_hash)
VALUES
('John Smith', 'john@example.com', 'password123'),
('Anna Brown', 'anna@example.com', 'password456');

INSERT INTO products (name, description, platform, region)
VALUES
('Minecraft', 'Sandbox game', 'PC', 'Global'),
('GTA V', 'Open world game', 'PC', 'Global'),
('EA Sports FC 26', 'Football game', 'PlayStation', 'Europe');

INSERT INTO sellers (name, rating)
VALUES
('GameStore', 4.8),
('DigitalGames', 4.5);

INSERT INTO offers (product_id, seller_id, price, stock)
VALUES
(1, 1, 19.99, 10),
(2, 1, 14.99, 15),
(3, 2, 49.99, 5);
