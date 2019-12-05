# Auto tag generator from embedded image keywords
This package will automatically create tags from IPTC keywords of an image and add them to the asset.
If the tag exists already, the image will be added to this keyword.

This package also checks if it finds information about copyright, caption and if missing a better title.

You can control the behavior with the following settings:

```
    DIU:
      MetaData:
        AutoTag:
          setTagsFromIptcKeywords: true
          setCopyrightNoticeFromIptc: true
          setTitleFromIptcTitle: true
          setCaptionFromIptcDescription: true
```

## Asset Title Update
The check for the asset title is a bit more complex it will check:
1) Title is not set
2) Title is just the filename

If on of the above cases are true it will check:
1) IPTC Title
2) If IPTC Title is also just a filename it will use IPTC Headline if available
