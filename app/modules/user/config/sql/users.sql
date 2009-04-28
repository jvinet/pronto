DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
	"id" SERIAL NOT NULL PRIMARY KEY,

	"access_keys" VARCHAR(255) NOT NULL,

	-- users can login with either OpenID or the traditional email/pass --
	"openid" VARCHAR(255),
	"email" VARCHAR(255) NOT NULL,
	"password" CHAR(40) NOT NULL,

	"first_name" VARCHAR(100) NOT NULL,
	"last_name" VARCHAR(100) NOT NULL,
	"language" VARCHAR(32) NOT NULL DEFAULT 'en',

	"created_on" DATE,
	"last_login" DATE,

	"status" VARCHAR(40) NOT NULL DEFAULT 'active',

	-- for any RDBMS that doesn't support ENUM --
	--"status" CHAR(7) NOT NULL DEFAULT 'active'

	"confirm_token" VARCHAR(32),
	"confirm_sent"  DATE

) /*! DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci */;
CREATE INDEX "users.access_keys" ON "users" ("access_keys");
CREATE INDEX "users.openid" ON "users" ("openid");
CREATE INDEX "users.email" ON "users" ("email");
CREATE INDEX "users.status" ON "users" ("status");
CREATE INDEX "users.confirm_token" ON "users" ("confirm_token");

-- password is 'pronto' --
INSERT INTO users (access_keys,first_name,last_name,email,password,created_on)
	VALUES ('Administrator','Admin','Istrator','admin@example.com','2726bab396d8ad823ac9be522c935006d1578cf2','2008-01-01');
