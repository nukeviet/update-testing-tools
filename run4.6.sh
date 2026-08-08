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

VERSIONS=(
  "f47de5cd6577d0d49449754be9bafe9081be4981" # 4.6.00
  # "head"                                     # latest
)
VERSIONS_NAME=(
  "4.6.00"
  "latest"
)
LASTESTVERSION="nukeviet4.6"
LASTESTUPDATEVERSION="to-4.6.01"

# Lấy NukeViet về thư mục src
if [ ! -d "$DIR_PATH/src" ]; then
  echo "Cloning NukeViet repository..."
  mkdir -p "$DIR_PATH/src"
  cd "$DIR_PATH/src"
  git clone https://github.com/nukeviet/nukeviet.git .
else
  cd "$DIR_PATH/src"
  git reset --hard HEAD
  git clean -dfx
  git checkout "$LASTESTVERSION"
  git pull
fi

# Lấy gói cập nhật về thư mục update
if [ ! -d "$DIR_PATH/update" ]; then
  echo "Cloning NukeViet update repository..."
  mkdir -p "$DIR_PATH/update"
  cd "$DIR_PATH/update"
  git clone https://github.com/nukeviet/update.git .
  git checkout "$LASTESTUPDATEVERSION"
else
  cd "$DIR_PATH/update"
  git reset --hard HEAD
  git clean -dfx
  git checkout "$LASTESTUPDATEVERSION"
  git pull
fi

for i in "${!VERSIONS[@]}"; do
  commitid="${VERSIONS[$i]}"
  version_name="${VERSIONS_NAME[$i]}"

  echo "=============================="
  echo "Testing on NukeViet version: $version_name"
  echo "=============================="
  echo ""

  # Làm sạch thư mục code và checkout về phiên bản tương ứng
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

  # Cài đặt website
  cd "$DIR_PATH"
  echo "Begin installation..."
  php $DIR_PATH/vendor/bin/codecept run -g install
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Test cập nhật phiên bản
  cp -rf "$DIR_PATH/update/install" "$DIR_PATH/src/"
  echo "Begin update testing..."
  php $DIR_PATH/vendor/bin/codecept run -g update
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Kiểm tra lại sau cập nhật
  echo "Begin verify testing..."
  php $DIR_PATH/vendor/bin/codecept run -g verify4.6
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Tests failed with code: $code on version $version_name"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  echo "Tests passed on version $version_name"
done

read -p "Finish All steps! Press any key to continue..."
