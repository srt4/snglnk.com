---
description: Deploy snglnk.com to production
---

1. Run rsync to deploy
// turbo
rsync -avz --exclude '.git' --exclude '.agent' --exclude '.DS_Store' ./ ubuntu@71.212.214.71:/var/www/snglnk.com/
