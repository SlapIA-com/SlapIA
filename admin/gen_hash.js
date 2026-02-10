const crypto = require('crypto');
// Simple SHA-256 hash for demo purposes since bcrypt module might not be installed
// Ideally we use bcrypt, but standard crypto is safer than cleartext
const hash = crypto.createHash('sha256').update('13062006Ytbesac..').digest('hex');
console.log(hash);
