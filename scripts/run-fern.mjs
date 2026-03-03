import { spawnSync } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDirectory = dirname(fileURLToPath(import.meta.url));
const repositoryRoot = resolve(scriptDirectory, '..');
const cliEntrypoint = resolve(repositoryRoot, 'node_modules', 'fern-api', 'cli.cjs');

const result = spawnSync(process.execPath, [cliEntrypoint, ...process.argv.slice(2)], {
    cwd: repositoryRoot,
    env: {
        ...process.env,
        HOME: repositoryRoot,
        FERN_DISABLE_TELEMETRY: 'true',
        FERN_NO_VERSION_REDIRECTION: 'true',
    },
    stdio: 'inherit',
});

if (result.error !== undefined) {
    throw result.error;
}

process.exit(result.status ?? 1);
