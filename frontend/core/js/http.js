export async function getJson(url) {
  const res = await fetch(url);
  return res.json();
}
