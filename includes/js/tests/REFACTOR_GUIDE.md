📁 YAP Tests - Refactored Structure

════════════════════════════════════════════════

✅ NOWA STRUKTURA:

```
includes/js/tests/
│
├── index.js                      Main loader (loads all tests)
├── test-config.js               Configuration (legacy, kept for compatibility)
│
├── utils/                        Shared utilities
│   ├── logger.js               Logging utility
│   ├── asserts.js              Assertion utility
│   └── TestRunner.js            Test execution engine
│
├── basic/                        Basic field editing tests (15)
│   ├── testOpenFieldSettings.js      Test 1
│   ├── testChangeFieldName.js        Test 2
│   ├── testChangeFieldLabel.js       Test 3
│   ├── testChangeFieldPlaceholder.js Test 4
│   ├── testChangeDefaultValue.js     Test 5
│   ├── testChangeDescription.js      Test 6
│   ├── testChangeCSSOClass.js        Test 7
│   ├── testToggleRequired.js         Test 8
│   ├── testTabSwitching.js           Test 9
│   ├── testConditionalLogic.js       Test 10
│   ├── testSaveAndClose.js           Test 11
│   ├── testCombinedChanges.js        Test 12
│   ├── testSubFieldEditing.js        Test 13
│   ├── testFieldNameValidation.js    Test 14
│   └── testModalClosing.js           Test 15
│
├── advanced/                     Advanced scenario tests (6)
│   ├── testBasicCombinations.js                Advanced Test 1
│   ├── testConditionalLogicOperators.js       Advanced Test 2
│   ├── testNestedFieldsEditing.js             Advanced Test 3
│   ├── testModifyAndRevert.js                 Advanced Test 4
│   ├── testSpecialCharacters.js               Advanced Test 5
│   └── testRapidChanges.js                    Advanced Test 6
│
└── standalone-runner.html        Standalone test interface
```

════════════════════════════════════════════════

✨ ZALETY:

1. ✅ Modular - Każdy test w osobnym pliku
2. ✅ Organized - Logiczna struktura folderów
3. ✅ Maintainable - Łatwo znaleźć i edytować test
4. ✅ Scalable - Łatwo dodać nowe testy
5. ✅ Reusable - Utilities można używać w innych testach
6. ✅ Clean - Separacja concerns

════════════════════════════════════════════════

🎯 UTILITIES:

logger.js
─────────
- TestLogger.log(message, type)
- TestLogger.group(title)
- TestLogger.groupEnd()
- TestLogger.clear()
- TestLogger.getResults()

asserts.js
──────────
- TestAssert.assert(condition, message)
- TestAssert.assertEqual(actual, expected, message)
- TestAssert.assertExists(element, selector, message)
- TestAssert.assertHasClass(element, className, message)
- TestAssert.assertValue(element, expectedValue, message)
- TestAssert.reset()
- TestAssert.getSummary()

TestRunner.js
─────────────
- TestRunner.register(name, testFn)
- TestRunner.runAll(title)
- TestRunner.ensureJQuery()
- TestRunner.printSummary()

════════════════════════════════════════════════

📖 USAGE:

1. W Visual Builder:
   F12 → Console → YAPBuilderTests.runAll()

2. W Developer Overlay:
   Kliknij kółko 🧪 → "Run All Tests"

3. W Standalone Runner:
   /includes/js/tests/standalone-runner.html
   → Click "Run All Tests"

════════════════════════════════════════════════

🔧 ADDING NEW TESTS:

1. Create file: basic/testNewFeature.js

2. Structure:
   ```javascript
   const testNewFeature = function() {
       const $ = window.jQuery;
       TestLogger.log('Testing new feature', 'test');
       
       // Your test logic
       TestAssert.assert(condition, 'Test message');
       
       return Promise.resolve(true);
   };
   
   TestRunner.register('Test N: New Feature', testNewFeature);
   window.testNewFeature = testNewFeature;
   ```

3. Add to index.js testFiles array:
   '/includes/js/tests/basic/testNewFeature.js'

════════════════════════════════════════════════

🚀 LOADING:

1. enqueue.php loads: index.js
2. index.js loads: utils/ files
3. index.js loads: basic/ tests
4. index.js loads: advanced/ tests
5. Tests are registered with TestRunner
6. APIs available: YAPBuilderTests, YAPAdvancedTests

════════════════════════════════════════════════

✅ COMPLETE REFACTOR DONE!

- 15 Basic tests (separate files)
- 6 Advanced tests (separate files)
- 3 Utility files (logger, asserts, runner)
- 1 Main index loader
- Organized folder structure
- Clean separation of concerns
