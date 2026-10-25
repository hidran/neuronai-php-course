# Verify references against a second invoice

Use the separate `exercise-invoice.pdf`, which contains a different invoice identity, total and review code. Verify the actual bytes and the real provider reference instead of relying on an answer that merely sounds plausible.

1. Encode the local PDF with `SourceExamples::encoded()` and check that strict Base64 decoding reconstructs its exact bytes. Check that a missing file is rejected before encoding.
2. Upload the exercise PDF to the Anthropic Files API with `requests/upload-exercise-invoice.http` in PhpStorm's HTTP client, using the private `studio` environment. The response is saved to `storage/exercise-upload.json`, and its `id` starts with `file_`. A placeholder ID must never stand in for this successful upload.
3. Build the inline and the reference `FileContent` blocks for the same question and measure both with `SourceExamples::mappedBytes()`. The reference must be smaller than the inline bytes.
4. Ask the same question in two fresh threads through the injected `SourceAgent`, once with inline bytes and once with the uploaded ID. Both answers must contain `BIRCH-964`. The question itself must not supply this answer.
5. Read the reported input tokens separately. A smaller payload is required here; a token reduction is not.

Copy `files/exercise-source-references.php` into `examples/` (the lesson's classes and fixtures are already in the project) and run:

```text
php examples/exercise-source-references.php
```

Expected fixed labels:

```text
Byte round trip and failed read: passed
Mapped inline and ID representations differ: passed
BASE64 document verified.
ID document verified.
Source reference exercise complete.
```

Measured mapped bytes, model replies and input token counts also appear. The exercise requires one upload and two real document requests on Anthropic (`NEURON_PROVIDER=anthropic`). Explicit exceptions enforce the checks regardless of PHP assertion settings. A provider reference belongs to the uploading provider and workspace; it is not a permanent business document identity. Delete the upload through the Files API when you no longer need it.
