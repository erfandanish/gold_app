

CREATE DATABASE IF NOT EXISTS gold_jewellery;
USE gold_jewellery;


DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS GoldProducts;
DROP TABLE IF EXISTS Customers;


CREATE TABLE Customers (
    customer_id     INT AUTO_INCREMENT PRIMARY KEY,
    customer_name   VARCHAR(100) NOT NULL
) ENGINE=InnoDB;


CREATE TABLE GoldProducts (
    product_id      INT AUTO_INCREMENT PRIMARY KEY,
    product_name    VARCHAR(150) NOT NULL,
    product_type    VARCHAR(50)  NOT NULL,
    weight_g        DECIMAL(10,3) NOT NULL,
    purity          SMALLINT NOT NULL
) ENGINE=InnoDB;


CREATE TABLE Orders (
    order_id        INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    product_id      INT NOT NULL,
    quantity        INT NOT NULL,
    order_date      DATE NOT NULL,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES Customers(customer_id),
    CONSTRAINT fk_orders_product  FOREIGN KEY (product_id)  REFERENCES GoldProducts(product_id)
) ENGINE=InnoDB;


INSERT INTO Customers (customer_id, customer_name) VALUES
    (1, 'Tan Mei Ling'),
    (2, 'Ahmad Faris'),
    (3, 'Priya Devi');

INSERT INTO GoldProducts (product_id, product_name, product_type, weight_g, purity) VALUES
    (1, 'Classic Wedding Band',   'Ring',    5.000, 916),
    (2, 'Rope Chain Necklace',    'Necklace',12.500, 999),
    (3, 'Dangling Hoop Earrings', 'Earrings', 3.200, 750);

INSERT INTO Orders (order_id, customer_id, product_id, quantity, order_date) VALUES
    (1, 1, 1, 2, '2026-08-01'),
    (2, 2, 2, 1, '2026-08-03'),
    (3, 3, 3, 3, '2026-08-05');



SELECT
    o.order_id         AS order_id,
    c.customer_name     AS customer_name,
    p.product_name      AS product_name,
    p.weight_g          AS product_weight_g,
    p.purity            AS gold_purity,
    o.quantity          AS quantity,
    o.order_date        AS order_date
FROM Orders o
JOIN Customers c    ON o.customer_id = c.customer_id
JOIN GoldProducts p ON o.product_id  = p.product_id
ORDER BY o.order_id;
