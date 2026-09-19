#!/usr/bin/env python3
"""Symbol cross-check for the Flutter app.

`flutter analyze` cannot run here (no SDK), so this checks the failure mode that
matters most: a screen referencing a class or provider that no file declares -
e.g. after a rename. It collects every declared top-level name in lib/ and flags
app-style identifiers (FooScreen, FooRepository, FooProvider, FooController,
FooDraft, FooResult, FooConfig) that are used but declared nowhere.
"""
import glob
import re
import sys

DECL_RE = re.compile(
    r"^\s*(?:abstract\s+)?(?:final\s+)?(?:sealed\s+)?"
    r"(?:class|enum|mixin|extension|typedef)\s+([A-Za-z_][A-Za-z0-9_]*)"
)
DECL_FN_RE = re.compile(
    r"^(?:final|const|var|late)\s+(?:[A-Za-z_<>?,\s]+?\s+)?"
    r"([A-Za-z_][A-Za-z0-9_]*)\s*=",
)
USE_RE = re.compile(
    r"\b([A-Z][A-Za-z0-9_]*(?:Screen|Repository|Provider|Controller|Draft|"
    r"Result|Config|Settings|Page|Service|Client|Model|Request|Response))\b"
)

files = sorted(glob.glob("lib/**/*.dart", recursive=True))

declared = set()
sources = {}
for path in files:
    body = open(path, encoding="utf-8").read()
    sources[path] = body
    for match in DECL_RE.finditer(body):
        declared.add(match.group(1))
    for match in DECL_FN_RE.finditer(body):
        declared.add(match.group(1))

# Anything from Flutter/Dart/packages is not ours to declare.
EXTERNAL = {
    "ApiClient",
    "DioException",
    "Provider",
    "StateProvider",
    "FutureProvider",
    "StreamProvider",
    "NotifierProvider",
    "AsyncNotifierProvider",
    "ConsumerWidget",
    "ConsumerStatefulWidget",
    "ConsumerState",
    "ChangeNotifier",
    "GoRouter",
    "GoRouterState",
    "RouteBase",
    "GoRoute",
    "StatefulWidget",
    "StatelessWidget",
    "MaterialApp",
    "MaterialPage",
    "MaterialPageRoute",
    "WidgetRef",
    "Ref",
    "AsyncValue",
    "Widget",
    "WidgetsFlutterBinding",
    "ThemeData",
    "ColorScheme",
    "TextTheme",
    "CircleAvatar",
    "FutureBuilder",
    "Snapshot",
    "DefaultAssetBundle",
    "ImageProvider",
    "NetworkImage",
    "FileImage",
    "AssetBundle",
    "ByteData",
    "RequestOptions",
    "Response",
    "BaseOptions",
    "InterceptorsWrapper",
    "FormData",
    "MultipartFile",
    "Uri",
    "DateTime",
    "Duration",
    "List",
    "Map",
    "Set",
    "Iterable",
    "Object",
    "String",
    "RegExp",
    "TextEditingController",
    "ScrollController",
    "FocusNode",
    "GlobalKey",
    "ValueKey",
    "PageController",
    "AnimationController",
    "TickerProviderStateMixin",
    "AutomaticKeepAliveClientMixin",
    "XFile",
    "ImagePicker",
    "ImageSource",
    "Hive",
    "SecureStorage",
    "FlutterSecureStorage",
    "SharedPreferences",
    "GoogleFonts",
    "Dio",
    "LogInterceptor",
    "ApiException",
    "TypeErrorException",
    "JsonResponseException",
    "Screen",
}

problems = []
for path in files:
    for number, line in enumerate(sources[path].splitlines(), start=1):
        if line.lstrip().startswith("import") or line.lstrip().startswith("export"):
            continue
        for match in USE_RE.finditer(line):
            name = match.group(1)
            if name in declared or name in EXTERNAL:
                continue
            problems.append(f"{path}:{number} uses undeclared {name}")

for problem in sorted(set(problems)):
    print(f"UNDECLARED {problem}")

print(f"checked {len(files)} dart files, {len(set(problems))} undeclared symbols")
sys.exit(1 if problems else 0)
