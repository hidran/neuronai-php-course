# Verify references against a second invoice

Use the separate `exercise-invoice.pdf`, which contains a different invoice identity, total and review code. Verify the actual bytes and the real provider reference instead of relying on an answer that merely sounds plausible.

1. Encode the local PDF and assert that strict Base64 decoding reconstructs its exact bytes. Reject a missing-file read before encoding.
2. Pass a `FileContent` with `SourceType::URL` through the installed OpenAI Chat Completions mapper. Check that only the accompanying text remains. This is a local regression check for an unsupported route, not a successful document request.
3. Upload the exercise PDF with `requests/upload-exercise-invoice.http` in PhpStorm's HTTP client, using your private provider environment. The response is saved to `storage/exercise-upload.json`. A placeholder ID must never stand in for this successful upload.
4. Inspect mapped payload shapes: the inline route must contain `file_data`; the reference route must contain the returned `file_id` and no embedded bytes. Compare serialized payload sizes.
5. Ask the same question in two fresh threads, once with inline bytes and once with that valid ID. Both answers must identify `BIRCH-964` from the second fixture. The question itself must not supply this answer.
6. Report input token usage separately. Smaller payloads are required here; a token reduction is not.

Copy `files/exercise-source-references.php` into `examples/`, keep both lesson classes installed, and run:

```text
php examples/exercise-source-references.php
```

Expected fixed labels:

```text
Byte round trip and failed read: passed
Unsupported file URL omission detected: passed
Mapped inline and ID representations differ: passed
BASE64 document verified.
ID document verified.
Source reference exercise complete.
```

Measured byte lengths, model replies and input token counts also appear. The exercise requires one provider upload and two real document inferences. Explicit exceptions enforce the checks regardless of PHP assertion settings. Provider references belong to the uploading provider/account; they are not permanent business document identities. The upload expires after one day and can also be deleted through the provider's documented file service.
