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

NUKEVIETREPOURL="https://github.com/nukeviet/nukeviet.git" # Repo NukeViet để test
UPDATEREPOURL="https://github.com/nukeviet/update.git" # Repo chứa gói cập nhật để test

# Chuẩn bị một thư mục làm việc sạch từ một repo git
# prepare_repo <thư mục> <git remote url> <nhánh hoặc commit>
# Nếu thư mục đã tồn tại nhưng không phải repo git hoặc remote url không khớp
# thì xóa thư mục đó đi và clone lại
prepare_repo() {
  local dir="$1"
  local url="$2"
  local ref="$3"
  local current_url=""

  if [ -d "$dir/.git" ]; then
    current_url="$(git -C "$dir" config --get remote.origin.url 2>/dev/null)"
    if [ "$current_url" != "$url" ]; then
      echo "Remote url of $dir is \"$current_url\", expected \"$url\". Removing..."
      rm -rf "$dir"
    fi
  elif [ -d "$dir" ]; then
    echo "$dir is not a git repository. Removing..."
    rm -rf "$dir"
  fi

  if [ ! -d "$dir" ]; then
    echo "Cloning $url into $dir..."
    mkdir -p "$dir"
    git clone "$url" "$dir"
    code=$?
    if [[ $code -gt 0 ]]; then
      echo "Git clone $url failed with code: $code"
      read -p "Error! Press any key to continue..."
      exit $code
    fi
  else
    git -C "$dir" reset --hard HEAD
    git -C "$dir" clean -dfx
    git -C "$dir" fetch --all --prune
  fi

  git -C "$dir" checkout "$ref"
  code=$?
  if [[ $code -gt 0 ]]; then
    echo "Git checkout $ref failed with code: $code"
    read -p "Error! Press any key to continue..."
    exit $code
  fi

  # Chỉ pull khi đang ở trên một nhánh, checkout theo commit id sẽ ở trạng thái detached HEAD
  if git -C "$dir" symbolic-ref -q HEAD >/dev/null; then
    git -C "$dir" pull
  fi
}

# Lấy NukeViet về thư mục src
prepare_repo "$DIR_PATH/src" "$NUKEVIETREPOURL" "$LASTESTVERSION"

# Lấy gói cập nhật về thư mục update
prepare_repo "$DIR_PATH/update" "$UPDATEREPOURL" "$LASTESTUPDATEVERSION"

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
