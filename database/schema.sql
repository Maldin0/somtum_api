CREATE TABLE sqlite_sequence(name,seq);
CREATE TABLE IF NOT EXISTS "Category" (

	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,

	name TEXT NOT NULL

);
CREATE TABLE Dishes (

	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,

	name TEXT NOT NULL,

	price INTEGER NOT NULL,

	C_id INTEGER NOT NULL,

	CONSTRAINT Dishes_Catagory_FK FOREIGN KEY (id) REFERENCES "Category"(id)

);
CREATE TABLE Reviews (

	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,

	stars INTEGER NOT NULL,

	note TEXT,

	date INTEGER NOT NULL

);
CREATE TABLE IF NOT EXISTS "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);
CREATE TABLE IF NOT EXISTS "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime);
CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));
CREATE TABLE IF NOT EXISTS "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");
CREATE TABLE IF NOT EXISTS "personal_access_tokens" ("id" integer primary key autoincrement not null, "tokenable_type" varchar not null, "tokenable_id" integer not null, "name" varchar not null, "token" varchar not null, "abilities" text, "last_used_at" datetime, "expires_at" datetime, "created_at" datetime, "updated_at" datetime);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens" ("tokenable_type", "tokenable_id");
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens" ("token");
CREATE TABLE Order_details (

	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,

	order_id INTEGER NOT NULL,

	dish_id INTEGER NOT NULL,

	status TEXT DEFAULT "not started" NOT NULL,

	quantity INTEGER NOT NULL,

	CONSTRAINT Order_details_Dishes_FK FOREIGN KEY (dish_id) REFERENCES Dishes(id),

	CONSTRAINT Order_details_Orders_FK FOREIGN KEY (order_id) REFERENCES Orders(id)

);
CREATE TABLE Orders (

	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,

	table_id INTEGER NOT NULL,

	paid INTEGER DEFAULT (0) NOT NULL,

	created_at TEXT NOT NULL,

	updated_at TEXT NOT NULL,

	CONSTRAINT Orders_Tables_FK FOREIGN KEY (table_id) REFERENCES Tables(num)

);
CREATE TABLE Tables (

	num INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT

, status TEXT NOT NULL DEFAULT 'available');
