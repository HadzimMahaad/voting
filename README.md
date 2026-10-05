# Election system (vanilla PHP)
1. mysql -u root < schema.sql   (edit DB credentials in app.php)
2. composer install
3. php -S localhost:8000 -> open /auth.php
4. User Vote login/registration is at `/auth.php`; Staff SPR login/registration is at `/auth.php/admin`. The first staff account becomes the superadmin; later staff registrations require superadmin approval.
5. Voters register and log in through User Vote. Each voter account can vote once per election.

For an existing database, add staff and approval flags to users:
`ALTER TABLE users ADD COLUMN is_staff TINYINT NOT NULL DEFAULT 0, ADD COLUMN approved TINYINT NOT NULL DEFAULT 1;`

Add the voter ID field for existing databases:
`ALTER TABLE users ADD COLUMN voter_id VARCHAR(40) DEFAULT NULL UNIQUE;`
Existing voter accounts with no voter ID can continue logging in with their registered email.

For an existing database, create the organization list table if needed, then add logo storage:
`CREATE TABLE IF NOT EXISTS organizations(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(200) NOT NULL UNIQUE);`
`ALTER TABLE organizations ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL;`

For an existing database, add candidate organization and member-photo storage:
`ALTER TABLE candidates ADD COLUMN organization_id INT DEFAULT NULL, ADD COLUMN image_path VARCHAR(255) DEFAULT NULL, ADD CONSTRAINT fk_candidates_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL;`

For an existing database, create the shared member directory and link current candidates:
`CREATE TABLE IF NOT EXISTS members(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,organization_id INT DEFAULT NULL,image_path VARCHAR(255) DEFAULT NULL,UNIQUE KEY uq_members_organization_name(organization_id,name),FOREIGN KEY(organization_id) REFERENCES organizations(id) ON DELETE SET NULL);`
`INSERT IGNORE INTO members(name,organization_id,image_path) SELECT name,organization_id,image_path FROM candidates WHERE name IS NOT NULL AND name<>'';`
`ALTER TABLE candidates ADD COLUMN member_id INT DEFAULT NULL, ADD UNIQUE KEY uq_candidates_election_member(election_id,member_id), ADD CONSTRAINT fk_candidates_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL;`
`UPDATE candidates c JOIN members m ON m.name=c.name AND m.organization_id <=> c.organization_id SET c.member_id=m.id WHERE c.member_id IS NULL;`

For an existing database, retain legacy anonymous vote markers:
`CREATE TABLE IF NOT EXISTS anonymous_voted(election_id INT NOT NULL,voter_token CHAR(64) NOT NULL,PRIMARY KEY(election_id,voter_token),FOREIGN KEY(election_id) REFERENCES elections(id) ON DELETE CASCADE);`
