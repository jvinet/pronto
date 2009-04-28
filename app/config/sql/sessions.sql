DROP TABLE IF EXISTS "sessions";
CREATE TABLE "sessions" (
	"id" VARCHAR(255) NOT NULL,
	"lastupdate" INT NOT NULL,
	"data" TEXT,

	PRIMARY KEY("id")
) /*! DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci */;
CREATE INDEX "sessions.lastupdate" ON "sessions" ("lastupdate");
