# Exercise: similarity does not settle a policy question

Use the embeddings provider your .env configures (EMBEDDINGS_PROVIDER, EMBEDDINGS_MODEL, EMBEDDINGS_DIMENSIONS),
built with EmbeddingsSettings::fromEnv and EmbeddingsFactory, for the question and all passages. Keep the original
text next to each vector.

Question: Can I return a damaged package?

- Customers may return damaged packages.
- Customers may not return damaged packages.
- Our quarterly revenue increased.

Embed the question and the three passages. Rank the passages by descending
cosine similarity and print each identifier, score and complete passage.
Do not assert an exact decimal score or a mandatory ordering between the
two policy statements. Explain which statement authorizes the return and
which denies it, regardless of how closely they rank. State why shared
topic information makes similarity alone insufficient for this decision.

Then deliberately compare a two coordinate vector with a one coordinate
vector. Catch the framework's VectorStoreException and explain why matching
lengths would still not prove compatible embedding spaces.

Acceptance: four embedding calls using one model and one dimension count,
descending numeric ranking, retained passage text, a reasoned negation
explanation and an explicit dimension mismatch rejection. Do not mix vectors
from a different model into the ranking. Try it twice, once with your default
provider and once with EMBEDDINGS_PROVIDER=ollama EMBEDDINGS_MODEL=nomic-embed-text
set in the shell, and compare the two rankings, never the two sets of scores.

The runnable reference is examples/exercise-embeddings.php. The deterministic
geometry checks are in examples/54-vector-contracts.php. Hosted calls need
credentials and connectivity; the geometry checks and Ollama do not need keys.
