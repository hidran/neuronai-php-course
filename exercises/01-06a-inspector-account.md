# Exercise: your own Inspector account, application and key

You need a browser and the course project. Nothing is installed and no code is written.

1. Open `https://inspector.dev`, choose **Start free** and create your account (Google, or name, email address and
   password). The free plan needs no credit card.
2. In the dashboard, choose **Add application**. Leave the optional project name empty, give the app a name you will
   recognize, for example `neuronai-course`, pick the **NeuronAI** tile and press **Create**.
3. Open the application's **Settings** page and find the card **Ingestion Key**. The key is shown masked: use
   **Copy** to put the whole key on the clipboard.
4. Open the `.env` file of the course project (create it from `.env.example` if you have not yet) and paste the key
   after `INSPECTOR_INGESTION_KEY=`. Leave the same line empty in `.env.example`.
5. If the project is a git repository, run `git status`. `.env` must not be listed: `.gitignore` ignores it.

Acceptance: your dashboard shows one application for the course; `.env` holds its key on the
`INSPECTOR_INGESTION_KEY` line; `.env.example` still has the empty line; git does not track `.env`.
If a key ever ends up in a commit, a screenshot or a chat, use **Regenerates** in the same card to replace it, and
put the new key in `.env`.
