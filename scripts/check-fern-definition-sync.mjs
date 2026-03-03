import { spawnSync } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDirectory = dirname(fileURLToPath(import.meta.url));
const repositoryRoot = resolve(scriptDirectory, '..');
const runFernScript = resolve(repositoryRoot, 'scripts', 'run-fern.mjs');

function run(command, args, options = {}) {
    const result = spawnSync(command, args, {
        cwd: repositoryRoot,
        stdio: 'inherit',
        ...options,
    });

    if (result.error !== undefined) {
        throw result.error;
    }

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

run(process.execPath, [runFernScript, 'write-definition', '--language', 'php']);

const diff = spawnSync('git', ['diff', '--quiet', '--exit-code', '--', 'fern/.definition'], {
    cwd: repositoryRoot,
    stdio: 'inherit',
});

if (diff.error !== undefined) {
    throw diff.error;
}

if (diff.status !== 0) {
    console.error('Fern definition is out of sync. Re-run `npm run fern:definition` and commit the updated files.');
    process.exit(diff.status ?? 1);
}
