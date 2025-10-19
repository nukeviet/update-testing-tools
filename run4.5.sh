#!/bin/bash

set +o history

SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  TARGET="$(readlink "$SOURCE")"
  if [[ $TARGET == /* ]]; then
    SOURCE="$TARGET"
  else
    DIR="$(dirname "$SOURCE")"
    SOURCE="$DIR/$TARGET"
  fi
done
DIR="$(cd -P "$(dirname "$SOURCE")" >/dev/null 2>&1 && pwd)"
cd "$DIR"
DIR_PATH=$PWD

if [ ! -f "$DIR_PATH/.env" ]; then
  read -p "Not found .env file in $DIR_PATH"
  exit 1
fi

# Khởi chạy selenium
LOGFILE=$(mktemp)
SELENIUM_CMD="selenium-standalone start"

cleanup() {
  echo "Stopping selenium..."
  if kill -0 $SELENIUM_PID 2>/dev/null; then
    kill $SELENIUM_PID
    wait $SELENIUM_PID
  fi
  rm -f "$LOGFILE"
}
trap cleanup EXIT INT TERM

echo "Starting selenium..."
$SELENIUM_CMD >"$LOGFILE" 2>&1 &
SELENIUM_PID=$!

FOUND=""
for i in {1..10}; do
  sleep 1
  if grep -q "Selenium started" "$LOGFILE"; then
    FOUND="yes"
    break
  fi
done

if [ "$FOUND" != "yes" ]; then
  echo "Selenium failed to start within 10 seconds."
  echo "Log output:"
  echo "----------------------"
  cat "$LOGFILE"
  echo "----------------------"
  read -p "Error! Press any key to continue..."
  exit 1
fi

if ! kill -0 $SELENIUM_PID 2>/dev/null; then
  echo "Selenium exited before"
  wait $SELENIUM_PID
  EXIT_CODE=$?
  if [ $EXIT_CODE -ne 0 ]; then
    read -p "Selenium exited with code: $EXIT_CODE"
    exit $EXIT_CODE
  fi
fi

# Lấy NukeViet về thư mục src
if [ ! -d "$DIR_PATH/src" ]; then
  echo "Cloning NukeViet repository..."
  mkdir -p "$DIR_PATH/src"
  cd "$DIR_PATH/src"
  git clone https://github.com/nukeviet/nukeviet.git .
fi

VERSIONS=(
  "3f966cf9426555e6863a985ed91e4ee5509be51e" # 4.5.00
  "e0606d556b63fb03b73bf5572264f6f8648fae59" # 4.5.01
  "1f328bb8cd256f88bd45fc3ec5a50ae951da2501" # 4.5.02
  "222a560cf5d051f8ea5c301c6b0ffc215122cbea" # 4.5.03
  "b43102f5829c0e5b5613d46a96f983f87a22aec6" # 4.5.04
  "fdf0e407808a6d96704bb1799e9bc9474de97514" # 4.5.05
  "c7580dbd4433aed886f714f187d90cb3707886fb" # 4.5.06
  "head"                                     # latest
)
VERSIONS_NAME=(
  "4.5.00"
  "4.5.01"
  "4.5.02"
  "4.5.03"
  "4.5.04"
  "4.5.05"
  "4.5.06"
  "latest"
)
LASTESTVERSION="nukeviet4.5"

for i in "${!VERSIONS[@]}"; do
  commitid="${VERSIONS[$i]}"
  version_name="${VERSIONS_NAME[$i]}"

  echo "=============================="
  echo "Testing on NukeViet version: $version_name"
  echo "=============================="
  echo ""

  cd "$DIR_PATH/src"
  git reset --hard HEAD
  git clean -dfx
  if [ "$commitid" == "head" ]; then
    commitid="$LASTESTVERSION"
  fi
  git checkout "$commitid"
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Git checkout $commitid failed with code: $code"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  cd "$DIR_PATH"
  echo "Begin test..."
  php $DIR_PATH/vendor/bin/codecept run
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi
done

read -p "Finish! Press any key to continue..."
