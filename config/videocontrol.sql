-- 管理员表
DROP TABLE IF EXISTS manager;
CREATE TABLE manager (
  id       INTEGER PRIMARY KEY AUTOINCREMENT,
  name     TEXT NOT NULL,
  password TEXT NOT NULL,
  type     INT                 DEFAULT 0 NOT NULL
);

INSERT INTO manager (name, password, type) VALUES ('admin', 'admin', 1);