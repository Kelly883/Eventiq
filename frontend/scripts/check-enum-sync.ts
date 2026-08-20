import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

interface CheckResult {
  passed: boolean;
  enumName: string;
  phpCases: string[];
  tsCases: string[];
  missingInTs: string[];
  extraInTs: string[];
}

const BACKEND_ENUMS_DIR = join(process.cwd(), '../backend/app/Features/Compliance/Enums');
const TS_FILE = join(process.cwd(), 'src/features/compliance/types/audit.ts');

const ENUM_MAP: Record<string, { tsTypeName: string; tsUnionName: string }> = {
  AuditLogAction: {
    tsTypeName: 'AuditLogAction',
    tsUnionName: 'AuditLogAction',
  },
  AuditLogTargetType: {
    tsTypeName: 'AuditLogTargetType',
    tsUnionName: 'AuditLogTargetType',
  },
  AuditLogStatus: {
    tsTypeName: 'AuditLogStatus',
    tsUnionName: 'AuditLogStatus',
  },
  ComplianceClassification: {
    tsTypeName: 'ComplianceClassification',
    tsUnionName: 'ComplianceClassification',
  },
};

function extractPhpEnumCases(filePath: string): string[] {
  const content = readFileSync(filePath, 'utf-8');
  const cases: string[] = [];

  const caseRegex = /case\s+\w+\s*=\s*['"]([^'"]+)['"]/g;
  let match: RegExpExecArray | null;

  while ((match = caseRegex.exec(content)) !== null) {
    cases.push(match[1]);
  }

  return cases;
}

function extractTsUnionCases(tsContent: string, typeName: string): string[] {
  const cases: string[] = [];

  // Match union type definitions: type Foo = 'a' | 'b' | 'c';
  const unionRegex = new RegExp(`export\\s+type\\s+${typeName}\\s*=\\s*([^;]+);`, 's');
  const unionMatch = unionRegex.exec(tsContent);

  if (!unionMatch) {
    return cases;
  }

  const unionBody = unionMatch[1];
  const stringLiteralRegex = /['"]([^'"]+)['"]/g;
  let match: RegExpExecArray | null;

  while ((match = stringLiteralRegex.exec(unionBody)) !== null) {
    cases.push(match[1]);
  }

  return cases;
}

function checkEnumSync(): CheckResult[] {
  const results: CheckResult[] = [];
  const tsContent = readFileSync(TS_FILE, 'utf-8');

  const phpFiles = readdirSync(BACKEND_ENUMS_DIR)
    .filter((file) => file.endsWith('.php'))
    .map((file) => join(BACKEND_ENUMS_DIR, file));

  for (const [phpEnumName, { tsTypeName }] of Object.entries(ENUM_MAP)) {
    const phpFile = phpFiles.find((f) => f.endsWith(`${phpEnumName}.php`));

    if (!phpFile) {
      console.error(`Missing PHP enum file for: ${phpEnumName}`);
      process.exit(1);
    }

    const phpCases = extractPhpEnumCases(phpFile);
    const tsCases = extractTsUnionCases(tsContent, tsTypeName);

    const missingInTs = phpCases.filter((c) => !tsCases.includes(c));
    const extraInTs = tsCases.filter((c) => !phpCases.includes(c));

    results.push({
      passed: missingInTs.length === 0 && extraInTs.length === 0,
      enumName: phpEnumName,
      phpCases,
      tsCases,
      missingInTs,
      extraInTs,
    });
  }

  return results;
}

function main(): void {
  const results = checkEnumSync();
  let hasErrors = false;

  for (const result of results) {
    console.log(`\nChecking ${result.enumName}:`);

    if (result.passed) {
      console.log(`  ✅ ${result.phpCases.length} cases match between PHP and TypeScript`);
      continue;
    }

    hasErrors = true;

    if (result.missingInTs.length > 0) {
      console.log(`  ❌ Missing in TypeScript (${result.missingInTs.length}):`);
      for (const missing of result.missingInTs) {
        console.log(`     - '${missing}'`);
      }
    }

    if (result.extraInTs.length > 0) {
      console.log(`  ⚠️  Extra in TypeScript (${result.extraInTs.length}):`);
      for (const extra of result.extraInTs) {
        console.log(`     - '${extra}'`);
      }
    }
  }

  if (hasErrors) {
    console.log('\n❌ Enum sync check failed. Update TypeScript unions to match PHP enum cases.');
    process.exit(1);
  }

  console.log('\n✅ All enum types are in sync.');
  process.exit(0);
}

main();
