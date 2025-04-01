# Coding Challenge MySQL

This repository contains a set of MySQL exercises based on an e-commerce schema. The schema includes the following tables:

- `customers`
- `products`
- `orders`
- `order_items`
- `reviews`

## Exercise Questions

### Question 1: Total Sales Revenue by Product
**Task:**  
Write a SQL query that computes the total revenue for each product. Revenue is defined as the sum of (`quantity` × `price`) from all associated order items. Your result should include the product ID, product name, and total revenue, sorted in descending order of revenue.

**Answer:**
```sql
SELECT
    p.id AS product_id,
    p.name AS product_name,
    SUM(oi.quantity * oi.price) AS total_revenue
FROM order_items oi
JOIN products p ON oi.product_id = p.id
GROUP BY p.id, p.name
ORDER BY total_revenue DESC;
```

---

### Question 2: Top Customers by Spending
**Task:**  
Write a SQL query to identify the top 5 customers based on total spending. Calculate each customer's total spending by summing up the value of all their orders (i.e., the sum of `quantity` × `price` for each order item). Return the customer ID, name, and total spending.

**Answer:**
```sql
SELECT
    o.customer_id,
    c.name,
    SUM(oi.quantity * oi.price) as total_spends
FROM
    orders o
JOIN
    order_items oi ON oi.order_id = o.id
LEFT JOIN
    customers c ON c.id=o.customer_id
GROUP BY
    o.customer_id
ORDER BY
    total_spends DESC
LIMIT 5;
```

---

### Question 3: Average Order Value per Customer
**Task:**  
Write a SQL query that calculates the average order value for each customer who has placed at least one order. The average order value should be computed as the total amount for an order divided by the number of orders for that customer. Return the customer ID, name, and average order value, sorted by average order value in descending order.

**Answer:**
```sql
SELECT
    o.customer_id,
    c.name,
    SUM(oi.quantity * oi.price) / COUNT(DISTINCT o.id) as average_amount
FROM
    orders o
JOIN
    order_items oi ON oi.order_id = o.id
LEFT JOIN
    customers c ON c.id=o.customer_id
GROUP BY
    o.customer_id
ORDER BY
    average_amount DESC;
```

---

### Question 4: Recent Orders
**Task:**  
Write a SQL query to list all orders placed within the last 30 days. For each order, return the order ID, the customer’s name, the order date, and the order status. Use an appropriate join between the `orders` and `customers` tables. You may assume the current date is given by `NOW()`.

**Answer:**
```sql
SELECT
    o.id,
    c.name,
    o.order_date,
    o.status
FROM
    orders o
LEFT JOIN
    customers c ON c.id=o.customer_id
WHERE
    o.order_date >= NOW() - INTERVAL 30 DAY
ORDER BY
    order_date DESC;
```

---

### Question 5: Running Total of Customer Spending
**Task:**  
Using a Common Table Expression (CTE), write a SQL query that returns, for each customer, a list of their orders ordered by `order_date` along with a running total of spending up to each order.

**Answer:**
```sql
WITH OrderTotals AS (
    SELECT
        o.customer_id,
        o.id AS order_id,
        o.order_date,
        SUM(oi.quantity * oi.price) AS order_total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.customer_id, o.id, o.order_date
)
SELECT
    customer_id,
    order_id,
    order_date,
    order_total,
    SUM(order_total) OVER (
        PARTITION BY customer_id ORDER BY order_date, order_id
    ) AS running_total
FROM OrderTotals
ORDER BY customer_id, order_date, order_id;
```

---

### Question 6: Product Review Summary
**Task:**  
Write a SQL query to generate a summary for each product that includes:
- Product ID  
- Product Name  
- Average Review Rating  
- Total Number of Reviews  

**Answer:**
```sql
SELECT
    p.id,
    p.name,
    AVG(r.rating) AS average_rating,
    COUNT(r.id) AS total_reviews
FROM
    products p
LEFT JOIN
    reviews r ON r.product_id=p.id
GROUP BY
    p.id
ORDER BY
    average_rating DESC, total_reviews DESC;
```

---

### Question 7: Customers Without Orders
**Answer:**
```sql
SELECT
    c.id AS customer_id,
    c.name AS customer_name
FROM
    customers c
LEFT JOIN
    orders o ON o.customer_id=c.id
WHERE
    o.id IS NULL;
```

---

### Question 8: Update Last Purchased Date
**Answer:**
```sql
UPDATE products p
SET last_purchased = (
    SELECT MAX(o.order_date)
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.product_id = p.id
)
WHERE EXISTS (
    SELECT 1
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.product_id = p.id
);
```

---

### Question 9: Transaction Scenario
**Answer:**
```sql
START TRANSACTION;

INSERT INTO orders (customer_id, order_date, status)
VALUES (10, NOW(), 'Pending');

SET @order_id = LAST_INSERT_ID();

INSERT INTO order_items (order_id, product_id, quantity, price)
VALUES (@order_id, 9, 2, 500.00);

UPDATE products p
JOIN order_items oi ON p.id = oi.product_id
SET p.stock = p.stock - oi.quantity, p.last_purchased = NOW()
WHERE oi.order_id = @order_id;

COMMIT;
```

---

### Question 10: Query Optimization and Indexing
**Answer:**
```sql
EXPLAIN SELECT
    p.id AS product_id,
    p.name AS product_name,
    SUM(oi.quantity * oi.price) AS total_revenue
FROM order_items oi
JOIN products p ON oi.product_id = p.id
GROUP BY p.id
ORDER BY total_revenue DESC;
```

**Optimization : Add indexing for joins**
```sql
CREATE INDEX order_items_product_id ON order_items(product_id);
CREATE INDEX products_id ON products(id);
```

---

### Question 11: Query Optimization Challenge
**Optimized Query:**
```sql
SELECT
    c.id AS customer_id,
    c.name AS customer_name,
    SUM(oi.quantity * oi.price) AS total_spends
FROM
    customers c
JOIN orders o ON o.customer_id = c.id
JOIN order_items oi ON oi.order_id = o.id
GROUP BY o.id
ORDER BY total_spends DESC;
```

